<?php

it('informa que a API está no ar e conectada ao PostgreSQL de testes', function () {
    $this->getJson('/api/health')
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('banco.conectado', true)
        ->assertJsonPath('banco.nome', 'agendaki_test');
});

it('libera CORS para o servidor de desenvolvimento do Vite', function () {
    $this->getJson('/api/health', ['Origin' => 'http://localhost:5173'])
        ->assertOk()
        ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
});
