<?php

class ResourceTest extends PHPUnit_Framework_TestCase {

    /**
     * @test
     */
    public function 空のcurl_initで生成されたリソースが同一にならない() {
        $this->assertNotSame(curl_init(), curl_init());

        $a = curl_init();
        $b = curl_init();
        $c = $a;
        $this->assertSame($a, $c);
        $this->assertNotSame($b, $c);
    }

    /**
     * @test
     */
    public function testResource() {
        $ch1 = curl_init();
        curl_setopt_array($ch1, array(
        ));

        $ch2 = curl_init();
        curl_setopt_array($ch2, array(
            CURLOPT_TIMEOUT    => 1
        ));

        $this->assertNotSame($ch1, $ch2);
    }
    
    /**
     * @test
     */
    public function 同一なものは状態が変化しても同一である() {
        $url = 'http://www.example.com/';
        $ch = curl_init($url);
        $this->assertSame($ch, $ch);
        curl_setopt_array($ch, array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS  => 2,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_RETURNTRANSFER => true,
        ));
        $this->assertSame($ch, $ch);
        $content = curl_exec($ch);
        $this->assertSame($ch, $ch);
    }
    
    /**
     * @test
     */
    public function リダイレクト301されたらurlの同一性は保持されない() {
        $url = 'http://mynichinoken.jp/mynichinoken'; //301リダイレクトされる

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS  => 2,
            CURLINFO_HEADER_OUT => true,
            CURLOPT_RETURNTRANSFER => true,
        ));

        $content = curl_exec($ch);
        $info = curl_getinfo($ch);
        $this->assertNotSame($info['redirect_url'], $url);
        $this->assertNotSame($info['url'], $url);

        curl_close($ch);
    }

    /**
     * @test
    */
    public function コピーされたchハンドラと元は別物である() {
        $ch = curl_init();
        $ch2 = curl_copy_handle($ch);
        $this->assertNotSame($ch, $ch2);
    }
}