<?php

namespace atoum\instrumentation\stream;

class wrapper {

    private $_stream     = null;
    private $_streamName = null;
    public $context      = null;



    public static function register ( ) {

        return stream_wrapper_register('instrument', 'atoum\instrumentation\stream\wrapper');
    }

    public function stream_cast ( $castAs ) {

        return false;
    }

    public function stream_close ( ) {

        if(true === @fclose($this->getStream())) {

            $this->_stream     = null;
            $this->_streamName = null;
        }

        return;
    }

    public function stream_eof ( ) {

        return feof($this->getStream());
    }

    public function stream_flush ( ) {

        return fflush($this->getStream());
    }

    public function stream_lock ( $operation ) {

        return flock($this->getStream(), $operation);
    }

    public function stream_open ( $path, $mode, $options, &$openedPath ) {

        if(false === in_array('instrument', stream_get_filters()))
            stream_filter_register(
                'instrument',
                'atoum\instrumentation\stream\filter'
            );

        $path = substr($path, strlen('instrument://'));

        preg_match(
            '#^(?:options=(?<options>[^/]*)/)?(?:resource=)?(?<resource>[^$]+)$#',
            $path,
            $matches
        );

        $options    = $matches['options'];
        $parameters = array(
            'coverage-transition' => true,
            'moles'               => true
        );

        if(!empty($options)) {

            preg_match_all(
                '#(?<flag>[+\-])?(?<option>[\w\-]+)#',
                $options,
                $submatches,
                PREG_SET_ORDER
            );

            foreach($submatches as $submatch) {

                if(!isset($parameters[$submatch['option']]))
                    continue;

                $parameters[$submatch['option']] = '-' !== $submatch['flag'];
            }
        }

        $path = $matches['resource'];

        if(null === $this->context)
            $openedPath = fopen($path, $mode, $options & STREAM_USE_PATH);
        else
            $openedPath = fopen(
                $path,
                $mode,
                $options & STREAM_USE_PATH,
                $this->context
            );

        $this->_stream     = $openedPath;
        $this->_streamName = $path;

        stream_filter_append(
            $this->_stream,
            'instrument',
            STREAM_FILTER_READ,
            $parameters
        );

        return true;
    }

    public function stream_read ( $count ) {

        return fread($this->getStream(), $count);
    }

    public function stream_seek ( $offset, $whence = SEEK_SET ) {

        return 0 === fseek($this->getStream(), $offset, $whence);
    }

    public function stream_set_option ( $option, $arg1, $arg2 ) {

        return false;
    }

    public function stream_stat ( ) {

        return fstat($this->getStream());
    }

    public function stream_tell ( ) {

        return ftell($this->getStream());
    }

    public function stream_truncate ( $size ) {

        return ftruncate($this->getStream(), $size);
    }

    public function stream_write ( $data ) {

        return fwrite($this->getStream(), $data);
    }

    public function dir_closedir ( ) {

        if(true === $handle = @closedir($this->getStream())) {

            $this->_stream     = null;
            $this->_streamName = null;
        }

        return $handle;
    }

    public function dir_opendir ( $path, $options ) {

        $handle = null;

        if(null === $this->context)
            $handle = @opendir($path);
        else
            $handle = @opendir($path, $this->context);

        if(false === $handle)
            return false;

        $this->_stream     = $handle;
        $this->_streamName = $path;

        return true;
    }

    public function dir_readdir ( ) {

        return readdir($this->getStream());
    }

    public function dir_rewinddir ( ) {

        return rewinddir($this->getStream());
    }

    public function mkdir ( $path, $mode, $options ) {

        if(null === $this->context)
            return mkdir(
                $path,
                $mode,
                $options | STREAM_MKDIR_RECURSIVE
            );

        return mkdir(
            $path,
            $mode,
            $options | STREAM_MKDIR_RECURSIVE,
            $this->context
        );
    }

    public function rename ( $from, $to ) {

        if(null === $this->context)
            return rename($from, $to);

        return rename($from, $to, $this->context);
    }

    public function rmdir ( $path, $options ) {

        if(null === $this->context)
            return rmdir($path);

        return rmdir($path, $this->context);
    }

    public function unlink ( $path ) {

        if(null === $this->context)
            return unlink($path);

        return unlink($path, $this->context);
    }

    public function url_stat ( $path, $flags ) {

        if($flags & STREAM_URL_STAT_LINK)
            return @lstat($path);

        return @stat($path);
    }

    protected function getStream ( ) {

        return $this->_stream;
    }

    protected function getStreamName ( ) {

        return $this->_streamName;
    }
}
