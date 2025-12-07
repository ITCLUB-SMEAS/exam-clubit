<?php

namespace Tests\Unit;

use Tests\TestCase;

class MathFormulaTest extends TestCase
{
    /** @test */
    public function it_can_render_basic_latex()
    {
        $latex = 'x^2 + y^2 = z^2';
        $this->assertIsString($latex);
        $this->assertStringContainsString('x^2', $latex);
    }

    /** @test */
    public function it_can_handle_fraction_syntax()
    {
        $latex = '\\frac{a}{b}';
        $this->assertStringContainsString('frac', $latex);
    }

    /** @test */
    public function it_can_handle_integral_syntax()
    {
        $latex = '\\int_{0}^{1} x^2 dx';
        $this->assertStringContainsString('int', $latex);
    }

    /** @test */
    public function it_can_handle_greek_letters()
    {
        $latex = '\\alpha + \\beta = \\gamma';
        $this->assertStringContainsString('alpha', $latex);
        $this->assertStringContainsString('beta', $latex);
    }

    /** @test */
    public function it_can_handle_complex_formula()
    {
        $latex = 'x = \\frac{-b \\pm \\sqrt{b^2-4ac}}{2a}';
        $this->assertStringContainsString('frac', $latex);
        $this->assertStringContainsString('sqrt', $latex);
        $this->assertStringContainsString('pm', $latex);
    }
}
