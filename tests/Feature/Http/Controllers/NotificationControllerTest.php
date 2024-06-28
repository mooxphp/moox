<?php

it('it has a route', function(){
    $this->get('notification')->assertOk()->assertSee('api');
});
