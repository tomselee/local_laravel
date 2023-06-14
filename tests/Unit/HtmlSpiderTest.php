<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Common\RequestTools;
use voku\helper\HtmlDomParser;

class HtmlSpiderTest extends TestCase
{
    public function testGetChanDao()
    {
        $content = RequestTools::make()->setCookies($this->fetchCookies())->setIsLogResult(true)->get('http://192.168.1.110:8891/my-bug-assignedTo.html');
        dd($content);
        $res = HtmlDomParser::file_get_html('http://192.168.1.110:8891/my.html');
        dd($res);
    }

    private function fetchCookies()
    {
        $cookieStr = 'lang=zh-cn; device=desktop; theme=default; keepLogin=on; lastProduct=11; sidebar_collapsed=false; preBranch=0; preProductID=11; cookie_token=5854327251cd813e4c0ec0c309dc068586fa9a51497d84cff599ebf12c3db63d; zentaosid=rlpcnb7a4n5lsm2111dlb3lu4k; projectTaskOrder=status%2Cid_desc; PHPSESSID=f8f8743eb18e790cd066286a43ea8dd4; bugModule=0; bugBranch=0; treeBranch=0; storyModule=0; storyBranch=0; productStoryOrder=id_desc; _gitlab_session=164d3270706e21e78c551654ab22cc3b; lastProject=6; za=litongzhi; zp=43bc63e0d0b8a47e434ca54a594e33ed5787f420; lastBugModule=0; qaBugOrder=id_desc; selfClose=1; windowWidth=1745; windowHeight=529';
        $cookies   = explode(';', $cookieStr);
        $cookieArr = [];
        foreach ($cookies as $item) {
            $each                      = explode('=', $item);
            $cookieArr[trim($each[0])] = $each[1];
        }
        return $cookieArr;
    }
}
