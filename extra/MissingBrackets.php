<?php

require __DIR__ . DIRECTORY_SEPARATOR . 'Autoloader.php';

$a = <<<'CODE'
<?php

function f ( ) {

    if(1)
        foreach($it as $v)
            if(2)$x++;
            else
                while(false);
    elseif(2)$c = function ( ) { $i; $j; $k; };
    else++$x;
}
CODE;
$s = new \atoum\instrumentation\sequence\matching(token_get_all($a));

echo $a, "\n\n\n", $s;
