<?php

require '/usr/local/lib/Hoa/Core/Core.php';

from('Hoathis')
-> import('Instrumentation.Sequence.Matching');

$m = new Hoathis\Instrumentation\Sequence\Matching(token_get_all(
    '<?php' . "\n\n" .
    'if ( $a && $b)' . "\n" .
    '    $a = 1;' . "\n\n" .
    'true + false;' . "\n" .
    'while(false);'
));

$m->skip([T_WHITESPACE]);

$m->match(
    [
        [
            ['if', '(', …, ')'],
            ['if', '(', 'mark_cond(', '\3', ')', ')']
        ],
        [
            [';'],
            [';', 'mark_line(__LINE__)', ';'],
            $m::SHIFT_REPLACEMENT_END
        ],
        [
            ['true', '+', 'false'],
            ['\1', '!', '\2', '!', '\3']
        ]
    ]
);

//print_r($m->getSequence());

foreach($m->getSequence() as $token)
    if(is_array($token))
        echo $token[1];
    else
        echo $token;
