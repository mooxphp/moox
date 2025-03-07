<?php

namespace Moox\Core\Console\Traits;

use function Laravel\Prompts\info;

trait ArtLeft
{
    public function art(): void
    {
        info('

 <fg=#9e0bee>▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓</> <fg=#9e0bee>▓▓▓▓▓▓▓▓▓▓▓</>       <fg=#9e0bee>▓▓▓▓▓▓▓▓▓▓▓▓</>           <fg=#9e0bee>▓▓▓▓▓▓▓▓▓▓▓▓</>   <fg=#9e0bee>▓▓▓▓▓▓▓</>        <fg=#9e0bee>▓▓▓▓▓▓▓</>
 <fg=#9e0bee>▓▓</><fg=#dc5ff4>▒░░</><fg=#9e0bee>▒▓▓</><fg=#dc5ff4>▒▒░░░░░░▒▒</><fg=#9e0bee>▓▓▓</><fg=#dc5ff4>▒░░░░░░░</><fg=#9e0bee>▒▓▓</>   <fg=#9e0bee>▓▓▓▓</><fg=#dc5ff4>▒░░░░░░░</><fg=#9e0bee>▒▓▓▓▓</>     <fg=#9e0bee>▓▓▓▓▓</><fg=#dc5ff4>▒░░░░░░░▒▒</><fg=#9e0bee>▓▓▓▓▓</><fg=#dc5ff4>▒▒▒▒</><fg=#9e0bee>▓▓</>      <fg=#9e0bee>▓▓▓</><fg=#dc5ff4>▒▒▒▒</><fg=#9e0bee>▓▓</>
 <fg=#9e0bee>▓</><fg=#dc5ff4>▒░░░░░░░░░░░░░░░░░░░░░░░░░░░░░</><fg=#9e0bee>▓▓▓▓▓</><fg=#dc5ff4>▒░░░░░░░░░░░░░</><fg=#9e0bee>▒▓▓▓</> <fg=#9e0bee>▓▓▓▓</><fg=#dc5ff4>▒░░░░░░░░░░░░░</><fg=#9e0bee>▒▓▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▒▓▓</>   <fg=#9e0bee>▓▓</><fg=#dc5ff4>▒░░░░░</><fg=#9e0bee>▓▓</>
 <fg=#9e0bee>▓</><fg=#dc5ff4>▒░░░░░░</><fg=#9e0bee>▒▓▓▓▓</><fg=#dc5ff4>▒░░░░░░░</><fg=#9e0bee>▒▓▓▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▒▓▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▒▓▓▓▓</><fg=#dc5ff4>▒░░░░░░░</><fg=#9e0bee>▓▓▓▓</><fg=#dc5ff4>░░░░░░</><fg=#9e0bee>▒▓▓▓▓▓</><fg=#dc5ff4>░░░░░░</><fg=#9e0bee>▒▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▒▓▓▓▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▒▓▓</>
 <fg=#9e0bee>▓</><fg=#dc5ff4>▒░░░░</><fg=#9e0bee>▓▓▓  ▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▓▓▓  ▓▓▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓▓▓   ▓▓▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▓</><fg=#dc5ff4>░░░░░░</><fg=#9e0bee>▓▓▓▓   ▓▓▓</><fg=#dc5ff4>▒░░░░</><fg=#9e0bee>▓▓▓</><fg=#dc5ff4>▒░░░░░</><fg=#9e0bee>▓▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▓▓▓</>
 <fg=#9e0bee>▓</><fg=#dc5ff4>▒░░░░</><fg=#9e0bee>▒▓    ▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▓▓    ▓▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓▓        ▓▓▓</><fg=#dc5ff4>░░</><fg=#9e0bee>▒</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▓▓▓        ▓▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓▓▓▓</><fg=#dc5ff4>░░░░░░░░░░░</><fg=#9e0bee>▓▓</>
 <fg=#9e0bee>▓</><fg=#dc5ff4>▒░░░░</><fg=#9e0bee>▒▓    ▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▓▓    ▓▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓          ▓▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▒▓▓          ▓▓</><fg=#dc5ff4>▒░░░░</><fg=#9e0bee>▓ ▓▓▓</><fg=#dc5ff4>░░░░░░░░░</><fg=#9e0bee>▓▓</>
 <fg=#9e0bee>▓</><fg=#dc5ff4>▒░░░░</><fg=#9e0bee>▒▓    ▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▓▓    ▓▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓▓        ▓▓</><fg=#dc5ff4>▒░░░░░</><fg=#9e0bee>▒</><fg=#dc5ff4>░░</><fg=#9e0bee>▒▓▓        ▓▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓▓▓</><fg=#dc5ff4>▒░░░░░</><fg=#9e0bee>▒</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▒▓</>
 <fg=#9e0bee>▓</><fg=#dc5ff4>▒░░░░</><fg=#9e0bee>▒▓    ▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▓▓    ▓▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓▓▓   ▓▓▓</><fg=#dc5ff4>▒░░░░░</><fg=#9e0bee>▒▒</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▒▓▓▓   ▓▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▓▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▒▓▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▒▓▓</>
 <fg=#9e0bee>▓</><fg=#dc5ff4>▒░░░░</><fg=#9e0bee>▒▓    ▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▓▓    ▓▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓▓▓</><fg=#dc5ff4>░░░░░░</><fg=#9e0bee>▒▒▓▓</><fg=#dc5ff4>▒░░░░░░</><fg=#9e0bee>▒▓▓▓▓</><fg=#dc5ff4>░░░░░░░</><fg=#9e0bee>▒▒▓▓</><fg=#dc5ff4>▒░░░░░░</><fg=#9e0bee>▓▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▒▓▓▓▓▓</><fg=#dc5ff4>▒░░░░░</><fg=#9e0bee>▓▓</>
 <fg=#9e0bee>▓</><fg=#dc5ff4>▒░░░░</><fg=#9e0bee>▒▓    ▓▓</><fg=#dc5ff4>░░░░░</><fg=#9e0bee>▓▓    ▓▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓▓▓▓</><fg=#dc5ff4>▒░░░░░░░░░░░░░</><fg=#9e0bee>▒▓▓▓ ▓▓▓▓</><fg=#dc5ff4>▒░░░░░░░░░░░░░</><fg=#9e0bee>▒▓▓</><fg=#dc5ff4>▒░░░░░</><fg=#9e0bee>▓▓▓   ▓▓</><fg=#dc5ff4>▒░░░░░</><fg=#9e0bee>▒▓</>
 <fg=#9e0bee>▓▓</><fg=#dc5ff4>░░░</><fg=#9e0bee>▒▓▓    ▓▓</><fg=#dc5ff4>▒░░░</><fg=#9e0bee>▒▓▓    ▓▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▓▓  ▓▓▓▓</><fg=#dc5ff4>▒░░░░░░</><fg=#9e0bee>▒▒▓▓▓▓     ▓▓▓▓▓</><fg=#dc5ff4>▒▒░░░░░</><fg=#9e0bee>▒▒▓▓▓▓▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓▓      ▓▓▓</><fg=#dc5ff4>░░░░</><fg=#9e0bee>▒▓</>
 <fg=#9e0bee>▓▓▓▓▓▓▓      ▓▓▓▓▓▓▓     ▓▓▓▓▓▓▓▓    ▓▓▓▓▓▓▓▓▓▓▓▓           ▓▓▓▓▓▓▓▓▓▓▓▓  ▓▓▓▓▓▓▓▓        ▓▓▓▓▓▓▓▓</>

        ');
    }
}
