![atoum](http://downloads.atoum.org/images/logo.png)

atoum is a **simple**, **modern** and **intuitive** unit testing framework for
PHP!

# atoum\instrumentation

This library is a proof-of-concept for code instrumentation. Please, see [the
presentation](http://hywan.github.io/atoum-instrumentation) or the following
sections.

## Example

    \atoum\instrumentation\stream\wrapper::register();
    require 'instrument://<options>/resource=<file>';

And a code like:

    <?php

    $a = 1;
    $b = 2;

    if(1 === $a) {

        $a += 3;
        $b  = 5;
    }

    class Foobar {

        public function firstMethod ( $x, $y = 5 ) {

            $this->compute($x);

            if($y < 5) {

                $this->compute($y);
            }

            return $x * $y;
        }
    }

    var_dump($a, $b);

…is instrumented as:

    <?php

    $a = 1;mark_line(__LINE__);
    $b = 2;mark_line(__LINE__);

    if(mark_cond(1 === $a)) {

        $a += 3;mark_line(__LINE__);
        $b  = 5;mark_line(__LINE__);
    }

    class Foobar {

        public function firstMethod ( $x, $y = 5 ) { if(mole_exists(__CLASS__ . '::firstMethod')) return mole_call(__CLASS__ . '::firstMethod');

            $this->compute($x);mark_line(__LINE__);

            if(mark_cond($y < 5)) {

                $this->compute($y);mark_line(__LINE__);
            }

            mark_line(__LINE__);return $x * $y;
        }
    }

    var_dump($a, $b);mark_line(__LINE__);

`mark_cond` and `mark_line` are added on-the-fly.

This example can be outdated since the code is updating often but it reflects the spirit.

## How does it work?

We have two layers.

The first one is `atoum\instrumentation\sequence\matching` that takes a sequence
as input and computes an instrumented/mutated sequence as output. This
instrumentation is based on “search/replace” rules, such as:

    ['if', '(', …, ')'] => ['if', '(', 'mark_cond(\3)', ')']

The second one is `atoum\instrumentation\stream\wrapper` that enables the
`instrument://` wrapper. Its role is to apply a stream filter on a certain
resource. We can parameterize this filter through the URI, such as:

    instrument://criteria=<criteria>/resource=<file>

Criteria (`criteria=…`) are option names concatenated by a comma with a `+` or a
`-` to enable or disable it. By default, all options are enabled. The following
example will diasble the “mole” instrumentation/rule:

    instrument://criteria=-moles/resource=<file>

The `criteria=…` part is optional.
The `resource=<file>` part can be shortened to `<file>`. It is present for
semantics only.

The stream filter `atoum\instrumentation\stream\filter` is a late computed
filter (inspired from
[`Hoa\Stream\Filter\LateComputed`](https://github.com/hoaproject/Stream/blob/master/Filter/LateComputed.php)).
Thus, we are able to compute the buffer when it contains all the content
of the resource. The computation made by the filter is… instrumentation.

Thus, when reading a resource through the `instrument://` wrapper, the content
is instrumented on-the-fly. No cache, no special steps, only prefix your
resource with `instrument://`.

### How does it work at the low-level?

When reading or writing a resource, data are carried into buckets (whose size is
equal to stream buffer). Buckets are exchanged from source to destination thanks
to brigades. When a brigade gives the content of a bucket to another brigade, a
filter can be applied on content. This filter is
`atoum\instrumentation\stream\filter` and is applied by
`atoum\instrumentation\stream\wrapper`.

The content is lexed with the native
[`token_get_all`](http://php.net/token_get_all) PHP function. We assume we
manipulate only PHP resources.

## License

atoum is under the New BSD License (BSD-3-Clause). Copyright Ivan Enderlin
(Hywan).
