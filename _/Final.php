<?php

require '/usr/local/lib/Hoa/Core/Core.php';

from('Hoa')
-> import('File.Read');

from('Hoathis')
-> import('Instrumentation.Stream.Wrapper', true);

$stream = new Hoa\File\Read('instrument://criteria=-nodes,+moles/resource=Test.php');

echo $stream->readAll();
