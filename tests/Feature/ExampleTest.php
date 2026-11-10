<?php

test('the homepage redirects to the ticket submission page', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('tickets.create'));
});

test('the public ticket submission page loads successfully', function () {
    $response = $this->get('/tickets/create');

    $response->assertStatus(200);
});