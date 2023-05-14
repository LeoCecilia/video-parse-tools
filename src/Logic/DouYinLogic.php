<?php

declare(strict_types=1);

namespace Smalls\VideoTools\Logic;

use Smalls\VideoTools\Enumerates\UserGentType;
use Smalls\VideoTools\Exception\ErrorVideoException;
use Smalls\VideoTools\Utils\CommonUtil;


/**
 * Created By 1
 * Author：smalls
 * Email：smalls0098@gmail.com
 * Date：2020/6/10 - 13:05
 **/
class DouYinLogic extends Base
{

    private $contents;
    private $itemId;

    public function setItemIds()
    {
        if (strpos($this->url, '/share/video')) {
            $url = $this->url;
        } else {
            $url = $this->redirects($this->url, [], [
                'User-Agent' => UserGentType::ANDROID_USER_AGENT,
            ]);
        }
        preg_match('/video\/([0-9]+)\//i', $url, $matches);
        if (CommonUtil::checkEmptyMatch($matches)) {
            throw new ErrorVideoException("item_id获取不到");
        }
        $this->itemId = $matches[1];
    }

    public function setContents()
    {
        $url = 'https://tiktok.iculture.cc/X-Bogus';
        $data = json_encode(array('url' => 'https://www.douyin.com/aweme/v1/web/aweme/detail/?aweme_id=' .  $this->itemId . '&aid=1128&version_name=23.5.0&device_platform=android&os_version=2333','user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36'));
        $header = array('Content-Type: application/json');
        $url = json_decode($this->curl($url, $header, $data), true)['param'];
        
        $msToken = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 107);
        $header = array('User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36', 'Referer: https://www.douyin.com/', 'Cookie: msToken='.$msToken.';odin_tt=324fb4ea4a89c0c05827e18a1ed9cf9bf8a17f7705fcc793fec935b637867e2a5a9b8168c885554d029919117a18ba69; ttwid=1%7CWBuxH_bhbuTENNtACXoesI5QHV2Dt9-vkMGVHSRRbgY%7C1677118712%7C1d87ba1ea2cdf05d80204aea2e1036451dae638e7765b8a4d59d87fa05dd39ff; bd_ticket_guard_client_data=eyJiZC10aWNrZXQtZ3VhcmQtdmVyc2lvbiI6MiwiYmQtdGlja2V0LWd1YXJkLWNsaWVudC1jc3IiOiItLS0tLUJFR0lOIENFUlRJRklDQVRFIFJFUVVFU1QtLS0tLVxyXG5NSUlCRFRDQnRRSUJBREFuTVFzd0NRWURWUVFHRXdKRFRqRVlNQllHQTFVRUF3d1BZbVJmZEdsamEyVjBYMmQxXHJcbllYSmtNRmt3RXdZSEtvWkl6ajBDQVFZSUtvWkl6ajBEQVFjRFFnQUVKUDZzbjNLRlFBNUROSEcyK2F4bXAwNG5cclxud1hBSTZDU1IyZW1sVUE5QTZ4aGQzbVlPUlI4NVRLZ2tXd1FJSmp3Nyszdnc0Z2NNRG5iOTRoS3MvSjFJc3FBc1xyXG5NQ29HQ1NxR1NJYjNEUUVKRGpFZE1Cc3dHUVlEVlIwUkJCSXdFSUlPZDNkM0xtUnZkWGxwYmk1amIyMHdDZ1lJXHJcbktvWkl6ajBFQXdJRFJ3QXdSQUlnVmJkWTI0c0RYS0c0S2h3WlBmOHpxVDRBU0ROamNUb2FFRi9MQnd2QS8xSUNcclxuSURiVmZCUk1PQVB5cWJkcytld1QwSDZqdDg1czZZTVNVZEo5Z2dmOWlmeTBcclxuLS0tLS1FTkQgQ0VSVElGSUNBVEUgUkVRVUVTVC0tLS0tXHJcbiJ9');
        $arr = json_decode($this->curl($url, $header), true);
        
        $video_url = $arr['aweme_detail']['video']['play_addr']['url_list'][0];
        if (empty($video_url)) {
            throw new ErrorVideoException('parsing failed');
        }
        $this->contents = $arr;
    }

    private function curl($url, $header = null, $data = null) {
        $con = curl_init((string)$url);
        curl_setopt($con, CURLOPT_HEADER, false);
        curl_setopt($con, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($con, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($con, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($con, CURLOPT_AUTOREFERER, 1);
        if (isset($header)) {
            curl_setopt($con, CURLOPT_HTTPHEADER, $header);
        }
        if (isset($data)) {
            curl_setopt($con, CURLOPT_POST, true);
            curl_setopt($con, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($con, CURLOPT_TIMEOUT, 5000);
        $result = curl_exec($con);
        return $result;
    }

    /**
     * @return mixed
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @return mixed
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    public function getVideoUrl()
    {
        if (empty($this->contents['aweme_detail']['video']['play_addr']['uri'])) {
            return '';
        }
        return $this->redirects('https://aweme.snssdk.com/aweme/v1/play/', [
            'video_id' => $this->contents['aweme_detail']['video']['play_addr']['uri'],
            'ratio'    => '720',
            'line'     => '0',
        ], [
            'User-Agent' => UserGentType::ANDROID_USER_AGENT,
        ]);
    }

    public function getVideoImage()
    {
        return CommonUtil::getData($this->contents['aweme_detail']['video']['cover']['url_list'][0]);
    }

    public function getVideoDesc()
    {
        return CommonUtil::getData($this->contents['aweme_detail']['desc']);
    }

    public function getUsername()
    {
        return CommonUtil::getData($this->contents['aweme_detail']['author']['nickname']);
    }

    public function getUserPic()
    {
        return CommonUtil::getData($this->contents['aweme_detail']['author']['avatar_thumb']['url_list'][0]);
    }
}
