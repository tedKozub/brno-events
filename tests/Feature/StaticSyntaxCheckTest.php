<?php

namespace Tests\Feature;

use Tests\TestCase;

class StaticSyntaxCheckTest extends TestCase
{
    public function test_all_blade_templates_contain_valid_javascript_syntax(): void
    {
        $nodeBin = trim(shell_exec('which node') ?: 'node');
        $script = base_path('scripts/check-blade-syntax.js');

        $this->assertFileExists($script, 'The Blade JS syntax check script must exist');

        $output = [];
        $returnCode = 0;
        exec("{$nodeBin} " . escapeshellarg($script), $output, $returnCode);

        $outputText = implode("\n", $output);
        $this->assertEquals(0, $returnCode, "Static JavaScript syntax check failed on Blade templates:\n{$outputText}");
        $this->assertStringContainsString('PASS', $outputText);
    }
}
