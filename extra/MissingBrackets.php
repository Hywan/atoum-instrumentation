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



exit;

//  '<?php' . "\n" .
//  'class C {' . "\n" .
//  '    function f ( ) {' . "\n" .
//  '        if(1)' . "\n" .
//  //'            if(42)$x++;' . "\n" .
//  //'            $e++;' . "\n" .
//  /*
//  '            if(2)' . "\n" .
//  '                $a++;' . "\n" .
//  '            elseif(3)' . "\n" .
//  */
//  '                foreach($foo as $r)' . "\n" .
//  '                    if(4) $x++;' . "\n" .
//  '                    else $x--;' . "\n" .
//  //'                $b++;' . "\n" .
//  '            else' . "\n" .
//  '                $c++;' . "\n" .
//  /*
//  '        else' . "\n" .
//  '            $a = function ( ) { $i; $j; $k; };' . "\n" .
//  //'        while(false);' . "\n" .
//  */
//  '    }' . "\n" .
//  '}'
