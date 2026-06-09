<?php

namespace Tests\Feature;

use App\Support\InstansiPemprovDkiCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstansiSkpdFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_form_shows_searchable_skpd_select(): void
    {
        InstansiPemprovDkiCatalog::syncToDatabase();

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('data-instansi-searchable', false)
            ->assertSee('Pilih instansi', false)
            ->assertSee('Dinas Kesehatan', false);
    }
}
