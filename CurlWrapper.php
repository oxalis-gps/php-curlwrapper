<?php
class CurlWrapper
{
    protected $_curl_channel_queue = array();
    protected $_requested_curl_channel = array();
    protected $_contents = array();
    
    public function __construct()
    {
    }

    public function setCurlOption($key, array $options)
    {
        if($this->hasCurlChannelQueue($key)) {
            return FALSE;
        }
        return curl_setopt_array($this->_curl_channel_queue[$key], $options);
    }
    

    // 未処理なcurlチャンネル
    public function hasCurlChannelQueue($key)
    {
        return array_key_exists($key, $this->_curl_channel_queue);
    }
    public function appendCurlChannelQueue($key, $options=NULL)
    {
        if(!$this->hasCurlChannelQueue($key)) {
            $ch = curl_init($key);
            
            if($options !== NULL) {
                curl_setopt_array($ch, $options);
            }
            
            $this->_curl_channel_queue[$key] = $ch;
        }
        return $this;
    }

    // 処理完了済みのcurlチャンネル
    public function hasRequestedCurlChannel($key)
    {
        return array_key_exists($key, $this->_requested_curl_channel);
    }
    public function _appendRequestedCurlChannel($key, $ch)
    {
        $this->_requested_curl_channel[$key] = $ch;
        return $this;
    }
    protected function getRequestedCurlChannel($key=NULL)
    {
        if($key !== NULL) {
            return $this->_requested_curl_channel[$key];
        }
        return $this->_requested_curl_channel;
    }

    public function execQueueRequest()
    {
        // リクエスト待ちのcurlチャンネルが無いので何もしない
        if(count($this->_curl_channel_queue) <= 0) {
            return NULL;
        }

        $mh = curl_multi_init();
        foreach($this->_curl_channel_queue as $ch) {
            // curlマルチに、chハンドラを全登録
            curl_multi_add_handle($mh, $ch);
        }

        do {
            $stat = curl_multi_exec($mh, $running);
        } while ($stat === CURLM_CALL_MULTI_PERFORM);
        if ( ! $running || $stat !== CURLM_OK) {
            throw new RuntimeException('いずれかのURLに問題が有ります');
        }

        do switch (curl_multi_select($mh)) {
                case -1:
                    usleep(10);
                    do {
                        $stat = curl_multi_exec($mh, $running);
                    } while ($stat === CURLM_CALL_MULTI_PERFORM);
                    continue 2;
                case 0:
                    continue 2;
                default:
                    do {
                        $stat = curl_multi_exec($mh, $running);
                    } while ($stat === CURLM_CALL_MULTI_PERFORM);

                    do if ($raised = curl_multi_info_read($mh, $remains)) {
                            $key = array_search($raised['handle'], $this->_curl_channel_queue, true);
                            if($key !== false) {
                                $this->addContents($key, curl_multi_getcontent($raised['handle']));

                                // リクエストに使用したcurlチャンネルを、キューから処理済みへ移動
                                $this->_appendRequestedCurlChannel($key, $raised['handle']);
                                unset($this->_curl_channel_queue[$key]);
                                curl_multi_remove_handle($mh, $raised['handle']);
                            }
                        } while ($remains);
            } while ($running);
        curl_multi_close($mh);
    }

    public function hasContents($key)
    {
        return array_key_exists($key, $this->_contents);
    }
    
    protected function addContents($key, $data)
    {
        $this->_contents[$key] = $data;
        return $this;
    }

    public function getContents($key=NULL)
    {
        // キーの指定が無ければ、現在contentsに保持している内容全てを返す
        if($key === NULL) {
            return $this->_contents;
        }

        // ちゃんとcurlチャンネルから内容がcontentsへ移動されていればここで返せる。
        if($this->hasContents($key)) {
            return $this->_contents[$key];
        }

        // 何らかの理由でcurlチャンネルからcontentsに内容が渡されてないので、渡してから返す。
        if($this->hasRequestedCurlChannel($key)) {
            $this->addContents($key, curl_multi_getcontent($this->getRequestedCurlChannel($key)));
            return $this->_contents[$key];
        }
    }

    public function getInfo($key=NULL)
    {
        if($key === NULL) {
            $curl_channels = $this->getRequestedCurlChannel();
            
            $info = array();
            foreach($curl_channels as $key => $ch) {
                $info[$key] = curl_getinfo($ch);
            }
            return $info;
        }

        if($this->hasRequestedCurlChannel($key)) {
            $curl_ch = $this->getRequestedCurlChannel($key);
            return curl_getinfo($curl_ch);
        } else {
            return false;
        }
    }
}
