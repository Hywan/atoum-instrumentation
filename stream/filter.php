<?php

namespace atoum\instrumentation\stream;

use atoum\instrumentation\sequence\matching;

class filter extends \php_user_filter {

    protected $_buffer = null;

    public function filter ( $in, $out, &$consumed, $closing ) {

        $return = PSFS_FEED_ME;

        while($iBucket = stream_bucket_make_writeable($in)) {

            $this->_buffer .= $iBucket->data;
            $consumed      += $iBucket->datalen;
        }

        if(null !== $consumed)
            $return = PSFS_PASS_ON;

        if(true === $closing) {

            $this->compute();
            $bucket = stream_bucket_new(
                $this->getStream(),
                $this->_buffer
            );
            $oBucket = stream_bucket_make_writeable($out);
            stream_bucket_append($out, $bucket);

            $return        = PSFS_PASS_ON;
            $this->_buffer = null;
        }

        return $return;
    }

    public function onCreate ( ) {

        return true;
    }

    public function onClose ( ) {

        return;
    }

    public function compute ( ) {

        $matching = new matching(token_get_all($this->_buffer));

        $rules      = array();
        $parameters = $this->getParameters();
        $enabled    = function ( $parameter ) use ( &$parameters ) {

            return    isset($parameters[$parameter])
                   && true === $parameters[$parameter];
        };
        $…          = \atoum\instrumentation\sequence\…;

        if(true === $enabled('moles'))
            $rules['method::start'][] = array(
                array('{'),
                function ( Array $variables ) use ( &$_markerCount ) {

                    $class = '\atoum\instrumentation\mole';

                    if(true === $variables['method']['static'])
                        $callable = '\'' . $variables['class']['name'] . '\', ' .
                                    '\'' . $variables['method']['name'] . '\'';
                    else
                        $callable = '$this, ' .
                                    '\'' . $variables['method']['name'] . '\'';

                    $id = $variables['class']['name'] . '::' .
                          $variables['method']['name'];

                    $code = 'if(' . $class . '::exists(array(' . $callable . '))) ' .
                            'return ' . $class . '::call(' .
                                'array(' . $callable . '), func_get_args()' .
                            '); ' .
                            '\atoum\instrumentation\codecoverage::mark(\'' .
                            $id . '\', ' . $_markerCount++ . ');';

                    return array('{', $code);
                },
                $matching::SHIFT_REPLACEMENT_END
            );

        if(true === $enabled('coverage-transition')) {

            $_coverageExport = array();
            $_markerCount    = 0;

            if(false === $enabled('moles'))
                $rules['method::start'][] = array(
                    array('{'),
                    function ( Array $variables ) use ( &$_markerCount ) {

                        $id = $variables['class']['name'] . '::' .
                              $variables['method']['name'];

                        return array(
                            '{',
                            '\atoum\instrumentation\codecoverage::mark(\'' .
                            $id . '\', ' . $_markerCount++ . ');'
                        );
                    }
                );

            $rules['method::end'][] = array(
                array(),
                function ( $variables ) use ( &$_markerCount, &$_coverageExport ) {

                    $id = $variables['class']['name'] . '::' .
                          $variables['method']['name'];

                    $_coverageExport[$id] = $_markerCount;
                    $_markerCount         = 0;

                    return array();
                }
            );

            $rules['if::condition::start'][]    =
            $rules['while::condition::start'][] = array(
                array('('),
                function ( $variables ) use ( &$_markerCount ) {

                    $id = $variables['class']['name'] . '::' .
                          $variables['method']['name'];

                    return array(
                        '(',
                        '\atoum\instrumentation\codecoverage::markCondition(' .
                        '\'' . $id . '\', ' . $_markerCount++ . ', '
                    );
                },
                $matching::SHIFT_REPLACEMENT_END
            );

            $rules['case::start'][] =
            $rules['default::start'][] = array(
                array(':'),
                function ( $variables ) use ( &$_markerCount ) {

                    $id = $variables['class']['name'] . '::' .
                          $variables['method']['name'];

                    return array(
                        ':',
                        '\atoum\instrumentation\codecoverage::markCondition(' .
                        '\'' . $id . '\', ' . $_markerCount++ . ', true);'
                    );
                },
                $matching::SHIFT_REPLACEMENT_END
            );

            $rules['if::condition::end'][]    =
            $rules['while::condition::end'][] = array(
                array(')'),
                array('))'),
                $matching::SHIFT_REPLACEMENT_END
            );

            $rules['for::condition::end'][]     =
            $rules['foreach::condition::end'][] = array(
                array(')', '{'),
                function ( $variables ) use ( &$_markerCount ) {

                    $id = $variables['class']['name'] . '::' .
                          $variables['method']['name'];

                    return array(
                        ')',
                        '{',
                        '\atoum\instrumentation\codecoverage::markCondition(' .
                        '\'' . $id . '\', ' . $_markerCount++ . ', true);'
                    );
                },
                $matching::SHIFT_REPLACEMENT_END
            );

            $rules['if::block::end'][]      =
            $rules['else::block::end'][]    =
            $rules['while::block::end'][]   =
            $rules['for::block::end'][]     =
            $rules['foreach::block::end'][] = array(
                array('}'),
                function ( $variables ) use ( &$_markerCount ) {

                    $id = $variables['class']['name'] . '::' .
                          $variables['method']['name'];

                    return array(
                        '}',
                        '\atoum\instrumentation\codecoverage::markJoin(' .
                        '\'' . $id . '\', ' . $_markerCount++ . ');'
                    );
                },
                $matching::SHIFT_REPLACEMENT_END
            );

            $rules['else::block::start'][] = array(
                array('{'),
                function ( $variables ) use ( &$_markerCount ) {

                    $id = $variables['class']['name'] . '::' .
                          $variables['method']['name'];

                    return array(
                        '{',
                        '\atoum\instrumentation\codecoverage::markCondition(' .
                        '\'' . $id . '\', ' . $_markerCount++ . ', true);'
                    );
                },
                $matching::SHIFT_REPLACEMENT_END
            );

            $rules['file::end'][] = array(
                array(),
                function ( $variables ) use ( &$_coverageExport ) {

                    return array(
                        'namespace { ' .
                        '\atoum\instrumentation\codecoverage::export(' .
                        var_export($_coverageExport, true) .
                        '); }'
                    );
                }
            );
        }

        $matching->skip(array(T_WHITESPACE, T_COMMENT, T_DOC_COMMENT));
        $matching->match($rules);

        $this->_buffer = $matching->__toString();

        return;
    }

    public function setName ( $name ) {

        $old              = $this->filtername;
        $this->filtername = $name;

        return $old;
    }

    public function setParameters ( $parameters ) {

        $old          = $this->params;
        $this->params = $parameters;

        return $old;
    }

    public function getName ( ) {

        return $this->filtername;
    }

    public function getParameters ( ) {

        return $this->params;
    }

    public function getStream ( ) {

        return $this->stream;
    }
}
