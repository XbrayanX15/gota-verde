<?php

test('la raíz redirige al inicio de sesión cuando no hay sesión', function () {
    $this->get('/')->assertRedirect(route('login'));
});
