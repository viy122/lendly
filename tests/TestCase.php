<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        // Keep PHPUnit compilation separate from the running local website.
        // On Windows, replacing a view that another PHP process is including
        // can fail with "Access is denied" and break an otherwise valid page.
        $compiledViews = dirname(__DIR__).'/storage/framework/testing/views';
        if (! is_dir($compiledViews)) {
            mkdir($compiledViews, 0755, true);
        }
        putenv('VIEW_COMPILED_PATH='.$compiledViews);
        $_ENV['VIEW_COMPILED_PATH'] = $compiledViews;
        $_SERVER['VIEW_COMPILED_PATH'] = $compiledViews;

        return parent::createApplication();
    }
}
