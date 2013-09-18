![Hoa](http://static.hoa-project.net/Image/Hoa_small.png)

Hoa is a **modular**, **extensible** and **structured** set of PHP libraries.
Moreover, Hoa aims at being a bridge between industrial and research worlds.

# Hoathis\Instrumentation

This library is a proof-of-concept for code instrumentation.

## Example

    from('Hoathis')
    -> import('Instrumentation.Stream.Wrapper', true); // force to load.

    require 'instrument://<options>/resource=<file>';

And a code like:

    <?php

    $a = 1;
    $b = 2;

    if(1 === $a) {

        $a += 3;
        $b  = 5;
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

    var_dump($a, $b);mark_line(__LINE__);

`mark_cond` and `mark_line` are added on-the-fly.

This example can be outdated since the code is updating often but it reflects the spirit.

## How does it work?

We have two layers.

The first one is `Hoathis\Instrumentation\Sequence\Matching` that takes a
sequence as input and computes an instrumented/mutated sequence as output. This
instrumentation is based on “search/replace” rules, such as:

    ['if', '(', …, ')'] => ['if', '(', 'mark_cond(\3), ')']

The second one is `Hoathis\Instrumentation\Stream\Wrapper` that enables the
`instrument://` wrapper. Its role is to apply a stream filter on a certain
resource. We can parameterize this filter through the URI, such as:

    instrument://criteria=node,condition,decision/resource=<file>

The stream filter `Hoathis\Instrumentation\Stream\Filter` is a
[`Hoa\Stream\Filter\LateComputed`](https://github.com/hoaproject/Stream/blob/master/Filter/LateComputed.php)
filter. Thus, we are able to compute the buffer when it contains all the content
of the resource. The computation made by the filter is… instrumentation.

Thus, when reading a resource through the `instrument://` wrapper, the content
is instrumented on-the-fly. No cache, no special steps, only prefix
your resource with `instrument://`.

### How does it work at the low-level?

When reading or writing a resource, data are carried into buckets (whose size is
equal to stream buffer). Buckets are exchanged from source to destination thanks
to brigades. When a brigade gives the content of a bucket to another brigade, a
filter can be applied on content. This filter is
`Hoathis\Instrumentation\Stream\Filter` and is applied by
`Hoathis\Instrumentation\Stream\Wrapper`.

The content is lexed with the native `token_get_all` PHP function. We assume we
manipulate only PHP resources.

## Documentation

Different documentations can be found on the website:
[http://hoa-project.net/](http://hoa-project.net/).

## License

Hoa is under the New BSD License (BSD-3-Clause). Please, see
[`LICENSE`](http://hoa-project.net/LICENSE).
