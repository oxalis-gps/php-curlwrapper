<?php
require_once('./CurlWrapper.php');
class CurlWrapperTest extends PHPUnit_Framework_TestCase {
    protected $_default_options;
    protected $_urls;
    protected $_curl;

    public static function setUpBeforeClass() { }
    
    protected function setUp()
    {
        $this->_default_options = array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS  => 2,
            CURLOPT_RETURNTRANSFER => true,
        );
        
        $this->_urls = array(
            'http://www.example.com/',
        );
        
        $this->_curl = new CurlWrapper();
        foreach($this->_urls as $url) {
            $this->_curl->appendCurlChannelQueue($url, $this->_default_options);
        }
    }
    
    /**
     * @test
     */
    public function 追加されたものが全て処理キューに入っている()
    {
        foreach($this->_urls as $url) {
            $this->assertSame($this->_curl->hasCurlChannelQueue($url), true);
        }
    }
    
    /**
     * @test
     */
    public function キューに存在しないものを持っているか問い合わせたらFalseになる()
    {
        $this->assertFalse($this->_curl->hasCurlChannelQueue('asdf'));
    }

    /**
     * @test
     */
    public function リクエスト実行前に結果を取ろうとしても空の配列しか得られない()
    {
        $this->assertEmpty($this->_curl->getContents());
        $this->assertInternalType('array', $this->_curl->getContents());
    }

    /**
     * @test
     */
    public function 存在しないキーでリクエスト前に結果を得ようとしても空の配列しか得られない()
    {
        $this->assertEmpty($this->_curl->getContents('hoge'));
    }

    /**
     * @test
     */
    public function 未リクエスト状態でhasContentsの結果はfalseとなる()
    {
        $this->assertEquals(false, $this->_curl->hasContents('testtesttest'));
    }

    /**
     * @test
     */
    public function リクエストした結果コンテンツは持ってる判断になるはず()
    {
        foreach($this->_urls as $url) {
            $this->assertEquals(false, $this->_curl->hasContents($url));
        }
        
        $this->_curl->execQueueRequest();
        
        foreach($this->_urls as $url) {
            $this->assertEquals(true, $this->_curl->hasContents($url));
        }
    }

    /**
     * @test
     */
    public function リクエスト後はリクエスト待ちから処理完了の方に移動されているはず()
    {
        foreach($this->_urls as $url) {
            $this->assertTrue($this->_curl->hasCurlChannelQueue($url));
            $this->assertFalse($this->_curl->hasRequestedCurlChannel($url));
        }
        
        $this->_curl->execQueueRequest();
        
        foreach($this->_urls as $url) {
            $this->assertFalse($this->_curl->hasCurlChannelQueue($url));
            $this->assertTrue($this->_curl->hasRequestedCurlChannel($url));
        }
    }

    /**
     * @test
     */
    public function リクエストしてない状態でgetInfoしても空っぽなはず()
    {
        $this->assertInternalType('array', $this->_curl->getInfo());
    }

    /**
     * @test
     */
    public function 存在しないキーでgetInfoするとfalseが返るはず()
    {
        $this->assertFalse($this->_curl->getInfo('asdf'));
    }

    /**
     * @test
     */
    public function リクエスト実行前はgetInfoしてもfalseだったものがリクエスト後は結果が得られるはず()
    {
        foreach($this->_urls as $url) {
            $this->assertFalse($this->_curl->getInfo($url));
        }

        $this->_curl->execQueueRequest();

        foreach($this->_urls as $url) {
            $this->assertInternalType('array', $this->_curl->getInfo($url));
        }
    }
    
    /**
     * @test
     */
    public function リクエストした後getInfoを指定無しで取ると全てのリクエスト分のinfoが配列で得られる()
    {
        $this->_curl->execQueueRequest();
        $this->assertInternalType('array', $this->_curl->getInfo());
        $this->assertEquals(count($this->_urls), count($this->_curl->getInfo()));
    }

    /**
     * @test
     */
    public function リクエストしていないものに対してリクエストしたかを確認するとfalseになる()
    {
        $this->assertFalse($this->_curl->hasRequestedCurlChannel('asdf'));
    }

    /**
     * @test
     */
    public function リクエスト後は全てtrueになるはず()
    {
        $this->_curl->execQueueRequest();

        foreach($this->_urls as $url) {
            $this->assertTrue($this->_curl->hasRequestedCurlChannel($url));
        }
    }

    public function tearDown() { }
    public static function tearDownAfterClass() { }
}