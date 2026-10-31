// Builds the QA report (.docx) from the last Selenium run: artifacts/results.jsonl + artifacts/screens/*.png.
// Usage: node tests/e2e/report/build-report.cjs [--out "C:\path\report.docx"]
// No dependencies: writes the OOXML parts and zips them with a small built-in zip writer.
const fs = require('fs');
const path = require('path');
const zlib = require('zlib');

const ARTIFACTS = path.join(__dirname, '..', 'artifacts');
const outArg = process.argv.indexOf('--out');
const OUT = outArg > -1 ? process.argv[outArg + 1] : path.join(ARTIFACTS, 'Lendly-QA-Test-Report.docx');
const content = require('./content.cjs');

const results = fs.readFileSync(path.join(ARTIFACTS, 'results.jsonl'), 'utf8').trim().split('\n').map(JSON.parse);
const runDate = new Date(results[results.length - 1].at);

// ---------- OOXML helpers ----------
const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
function runs(text, base = '') {
  return String(text).split(/(`[^`]+`|\*\*[^*]+\*\*)/).filter(Boolean).map((part) => {
    let rpr = base;
    let t = part;
    if (part.startsWith('`')) { t = part.slice(1, -1); rpr += '<w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/><w:sz w:val="18"/>'; }
    else if (part.startsWith('**')) { t = part.slice(2, -2); rpr += '<w:b/>'; }
    return `<w:r>${rpr ? `<w:rPr>${rpr}</w:rPr>` : ''}<w:t xml:space="preserve">${esc(t)}</w:t></w:r>`;
  }).join('');
}
const p = (text, style) => `<w:p>${style ? `<w:pPr><w:pStyle w:val="${style}"/></w:pPr>` : ''}${runs(text)}</w:p>`;
const list = (items, numId) => items.map((t) => `<w:p><w:pPr><w:pStyle w:val="ListParagraph"/><w:numPr><w:ilvl w:val="0"/><w:numId w:val="${numId}"/></w:numPr></w:pPr>${runs(t)}</w:p>`).join('');
const code = (lines) => lines.map((l) => `<w:p><w:pPr><w:spacing w:after="0"/><w:ind w:left="360"/></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Consolas" w:hAnsi="Consolas"/><w:sz w:val="18"/></w:rPr><w:t xml:space="preserve">${esc(l)}</w:t></w:r></w:p>`).join('') + p('');
const pageBreak = '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';

const media = [];
function image(file, widthCm) {
  const buf = fs.readFileSync(file);
  const w = buf.readUInt32BE(16);
  const h = buf.readUInt32BE(20);
  media.push(buf);
  const n = media.length;
  const cx = Math.round(widthCm * 360000);
  const cy = Math.round(cx * (h / w));
  return `<w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0"><wp:extent cx="${cx}" cy="${cy}"/><wp:docPr id="${n}" name="Screenshot ${n}"/><a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:nvPicPr><pic:cNvPr id="${n}" name="image${n}.png"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="rIdImg${n}"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="${cx}" cy="${cy}"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r>`;
}

const TOTAL = 9638;
function table(rows, widths, opts = {}) {
  const w = widths || rows[0].map(() => Math.floor(TOTAL / rows[0].length));
  const cell = (c, i, header) => {
    const isObj = c && typeof c === 'object';
    const inner = isObj && c.xml ? c.xml : runs(isObj ? c.text : c, header ? '<w:b/>' : (isObj && c.color ? `<w:b/><w:color w:val="${c.color}"/>` : ''));
    const fill = header ? 'DCE6F2' : isObj && c.fill ? c.fill : null;
    return `<w:tc><w:tcPr><w:tcW w:w="${w[i]}" w:type="dxa"/>${fill ? `<w:shd w:val="clear" w:color="auto" w:fill="${fill}"/>` : ''}<w:vAlign w:val="${opts.vTop ? 'top' : 'center'}"/></w:tcPr><w:p><w:pPr><w:spacing w:after="0"/></w:pPr>${inner}</w:p></w:tc>`;
  };
  const tr = (r, header) => `<w:tr>${header ? '<w:trPr><w:tblHeader/></w:trPr>' : '<w:trPr><w:cantSplit/></w:trPr>'}${r.map((c, i) => cell(c, i, header)).join('')}</w:tr>`;
  return `<w:tbl><w:tblPr><w:tblStyle w:val="TableGrid"/><w:tblW w:w="${TOTAL}" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblCellMar><w:top w:w="50" w:type="dxa"/><w:left w:w="80" w:type="dxa"/><w:bottom w:w="50" w:type="dxa"/><w:right w:w="80" w:type="dxa"/></w:tblCellMar></w:tblPr><w:tblGrid>${w.map((x) => `<w:gridCol w:w="${x}"/>`).join('')}</w:tblGrid>${rows.map((r, i) => tr(r, i === 0)).join('')}</w:tbl>` + p('');
}

// ---------- body ----------
const LEVELS = [
  { prefix: 'C', name: 'Component tests', blurb: content.levels.C },
  { prefix: 'M', name: 'Module tests', blurb: content.levels.M },
  { prefix: 'I', name: 'Integrated tests (full rental cycle)', blurb: content.levels.I },
  { prefix: 'S', name: 'Smoke tests', blurb: content.levels.S },
];
const byLevel = (prefix) => results.filter((r) => r.id.startsWith(prefix + '-'));
const passed = results.filter((r) => r.result === 'PASS').length;
const resultCell = (r) => ({ text: r.result === 'PASS' ? 'PASS' : 'FAIL', color: r.result === 'PASS' ? '1E7B34' : 'C00000', fill: r.result === 'PASS' ? 'E8F5E9' : 'FDECEA' });
const title = (r) => {
  const t = r.title.replace(/^[A-Z]+-\d+\s*/, '');
  return t.charAt(0).toUpperCase() + t.slice(1);
};
const dateText = runDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', timeZone: 'Asia/Manila' });
// When the test finished, in Philippine time, e.g. "Oct 16, 2026 8:41:07 PM".
const timestamp = (iso) => {
  const d = new Date(iso);
  const opts = { timeZone: 'Asia/Manila' };
  return `${d.toLocaleDateString('en-US', { ...opts, month: 'short', day: 'numeric', year: 'numeric' })} ${d.toLocaleTimeString('en-US', { ...opts, hour: 'numeric', minute: '2-digit', second: '2-digit' })}`;
};

const body = [
  p(content.title, 'Title'),
  p(`${dateText} · ${content.tester}`),

  p('Summary', 'Heading1'),
  p(`**Verdict: ${passed === results.length ? 'GO' : 'NO GO'}.** ${passed} of ${results.length} Selenium tests passed on ${dateText}, across component, module and integrated levels. Every test has a screenshot in the test-case tables below. The dev team's own backend suite (PHPUnit) also passed: ${content.phpunit.summary}`),
  table([
    ['Level', 'What it checks', 'Tests', 'Passed', 'Failed'],
    ...LEVELS.map((l) => { const rs = byLevel(l.prefix); return [l.name, l.blurb, String(rs.length), String(rs.filter((r) => r.result === 'PASS').length), String(rs.filter((r) => r.result !== 'PASS').length)]; }),
    ['**Total (Selenium)**', '', `**${results.length}**`, `**${passed}**`, `**${results.length - passed}**`],
    ['Backend unit/feature (PHPUnit)', content.phpunit.what, content.phpunit.total, content.phpunit.passed, content.phpunit.failed],
  ], [2300, 4538, 900, 950, 950]),

  p('Scope and environment', 'Heading1'),
  table([['Item', 'Value'], ...content.environment], [2600, 7038]),

  p('Defects and findings', 'Heading1'),
  p(content.findingsLead),
  table([['ID', 'Finding', 'Steps / evidence', 'Expected', 'Actual', 'Severity'], ...content.findings], [700, 1700, 2338, 1700, 2300, 900], { vTop: true }),
  p('Environment notes (not app defects):'),
  list(content.envNotes, 1),

  p('Recommendations for devs', 'Heading1'),
  ...content.recommendations.flatMap(([head, items]) => [p(head, 'Heading2'), list(items, 1)]),

  ...LEVELS.flatMap((l) => {
    const rs = byLevel(l.prefix);
    if (!rs.length) return [];
    return [
      pageBreak,
      p(`Test cases — ${l.name}`, 'Heading1'),
      p(l.blurb),
      table([
        ['ID', 'Area', 'Test case and expected result', 'Result', 'Date & time (PHT)', 'Screenshot'],
        ...rs.map((r) => [
          `**${r.id}**`,
          r.suite.replace(/^full cycle:.*$/, 'Full rental cycle').replace(/^smoke: /, 'Smoke: '),
          r.error ? `${title(r)} — Failed: ${r.error}` : title(r),
          resultCell(r),
          timestamp(r.at),
          r.screenshot ? { xml: image(path.join(ARTIFACTS, r.screenshot), 6.4) } : 'No screenshot',
        ]),
      ], [700, 1150, 2150, 760, 1150, 3728], { vTop: true }),
    ];
  }),

  pageBreak,
  p('How to run the tests', 'Heading1'),
  p(content.howToRun.lead),
  list(content.howToRun.steps, 2),
  code(content.howToRun.commands),
  p(content.howToRun.after),
].join('');

// ---------- package ----------
const W = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"';
const NS = `${W} xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"`;
const files = {
  '[Content_Types].xml': `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Default Extension="png" ContentType="image/png"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/><Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/></Types>`,
  '_rels/.rels': `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>`,
  'word/_rels/document.xml.rels': `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering" Target="numbering.xml"/>${media.map((_, i) => `<Relationship Id="rIdImg${i + 1}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/image${i + 1}.png"/>`).join('')}</Relationships>`,
  'word/styles.xml': `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:styles ${W}><w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:eastAsia="Calibri" w:cs="Calibri"/><w:sz w:val="21"/><w:szCs w:val="21"/></w:rPr></w:rPrDefault><w:pPrDefault><w:pPr><w:spacing w:after="120" w:line="264" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults><w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style><w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:after="80"/></w:pPr><w:rPr><w:b/><w:color w:val="1F3864"/><w:sz w:val="40"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:spacing w:before="320" w:after="120"/><w:outlineLvl w:val="0"/></w:pPr><w:rPr><w:b/><w:color w:val="1F3864"/><w:sz w:val="30"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:pPr><w:keepNext/><w:spacing w:before="200" w:after="80"/><w:outlineLvl w:val="1"/></w:pPr><w:rPr><w:b/><w:color w:val="2E5597"/><w:sz w:val="24"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="ListParagraph"><w:name w:val="List Paragraph"/><w:basedOn w:val="Normal"/><w:pPr><w:spacing w:after="60"/><w:ind w:left="720"/></w:pPr></w:style><w:style w:type="table" w:styleId="TableGrid"><w:name w:val="Table Grid"/><w:tblPr><w:tblBorders><w:top w:val="single" w:sz="4" w:space="0" w:color="A6A6A6"/><w:left w:val="single" w:sz="4" w:space="0" w:color="A6A6A6"/><w:bottom w:val="single" w:sz="4" w:space="0" w:color="A6A6A6"/><w:right w:val="single" w:sz="4" w:space="0" w:color="A6A6A6"/><w:insideH w:val="single" w:sz="4" w:space="0" w:color="A6A6A6"/><w:insideV w:val="single" w:sz="4" w:space="0" w:color="A6A6A6"/></w:tblBorders></w:tblPr><w:rPr><w:sz w:val="19"/></w:rPr></w:style></w:styles>`,
  'word/numbering.xml': `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:numbering ${W}><w:abstractNum w:abstractNumId="0"><w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText w:val="•"/><w:lvlJc w:val="left"/><w:pPr><w:ind w:left="720" w:hanging="360"/></w:pPr></w:lvl></w:abstractNum><w:abstractNum w:abstractNumId="1"><w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="decimal"/><w:lvlText w:val="%1."/><w:lvlJc w:val="left"/><w:pPr><w:ind w:left="720" w:hanging="360"/></w:pPr></w:lvl></w:abstractNum><w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num><w:num w:numId="2"><w:abstractNumId w:val="1"/></w:num></w:numbering>`,
  'word/document.xml': `<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document ${NS}><w:body>${body}<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="567" w:footer="567" w:gutter="0"/></w:sectPr></w:body></w:document>`,
};
media.forEach((buf, i) => { files[`word/media/image${i + 1}.png`] = buf; });

// ---------- minimal zip writer ----------
const CRC_TABLE = Array.from({ length: 256 }, (_, n) => { let c = n; for (let k = 0; k < 8; k++) c = c & 1 ? 0xedb88320 ^ (c >>> 1) : c >>> 1; return c >>> 0; });
const crc32 = (buf) => { let c = 0xffffffff; for (const b of buf) c = CRC_TABLE[(c ^ b) & 0xff] ^ (c >>> 8); return (c ^ 0xffffffff) >>> 0; };
function zip(entries) {
  const locals = [];
  const centrals = [];
  let offset = 0;
  for (const [name, data] of Object.entries(entries)) {
    const raw = Buffer.isBuffer(data) ? data : Buffer.from(data, 'utf8');
    const deflated = zlib.deflateRawSync(raw);
    const nameBuf = Buffer.from(name, 'utf8');
    const crc = crc32(raw);
    const local = Buffer.alloc(30);
    local.writeUInt32LE(0x04034b50, 0); local.writeUInt16LE(20, 4); local.writeUInt16LE(0x0800, 6); local.writeUInt16LE(8, 8);
    local.writeUInt32LE(0, 10); local.writeUInt32LE(crc, 14); local.writeUInt32LE(deflated.length, 18); local.writeUInt32LE(raw.length, 22);
    local.writeUInt16LE(nameBuf.length, 26); local.writeUInt16LE(0, 28);
    const central = Buffer.alloc(46);
    central.writeUInt32LE(0x02014b50, 0); central.writeUInt16LE(20, 4); central.writeUInt16LE(20, 6); central.writeUInt16LE(0x0800, 8); central.writeUInt16LE(8, 10);
    central.writeUInt32LE(0, 12); central.writeUInt32LE(crc, 16); central.writeUInt32LE(deflated.length, 20); central.writeUInt32LE(raw.length, 24);
    central.writeUInt16LE(nameBuf.length, 28); central.writeUInt32LE(offset, 42);
    locals.push(local, nameBuf, deflated);
    centrals.push(central, nameBuf);
    offset += 30 + nameBuf.length + deflated.length;
  }
  const centralBuf = Buffer.concat(centrals);
  const end = Buffer.alloc(22);
  const count = Object.keys(entries).length;
  end.writeUInt32LE(0x06054b50, 0); end.writeUInt16LE(count, 8); end.writeUInt16LE(count, 10);
  end.writeUInt32LE(centralBuf.length, 12); end.writeUInt32LE(offset, 16);
  return Buffer.concat([...locals, centralBuf, end]);
}

fs.writeFileSync(OUT, zip(files));
console.log(`Report written: ${OUT} (${results.length} test cases, ${media.length} screenshots)`);
