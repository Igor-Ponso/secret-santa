<?php
// This file exists purely to help some IDEs/static analysers recognize the Laravel TestCase methods
// when using Pest's functional test style. It is not executed.

namespace Tests;

/**
 * @mixin \Illuminate\Foundation\Testing\Concerns\InteractsWithHttp
 * @mixin \Illuminate\Foundation\Testing\Concerns\InteractsWithAuthentication
 */
abstract class FrameworkExpectations extends TestCase
{
}
