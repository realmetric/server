<?php

namespace App\Controllers;

use App\Biz\Event;
use App\Keys;
use Psr\Http\Message\ServerRequestInterface;

class TrackController extends AbstractController
{
    public function create(ServerRequestInterface $request)
    {
        $data = $request->getParsedBody();
        $added = $this->redis->sAdd(Keys::REDIS_SET_TRACK_QUEUE, json_encode($data));

//        $data = '{"e":[{"t":1489440365,"v":1,"m":0,"s":[[0,0],[1,1],[2,2],[3,3]]},{"t":1489440365,"v":1,"m":0,"s":[[0,4],[1,5],[2,2],[3,3]]},{"t":1489440365,"v":1,"m":0,"s":[[0,0],[1,1],[2,6],[3,3]]},{"t":1489440365,"v":1,"m":1,"s":[[4,7]]},{"t":1489440365,"v":1,"m":0,"s":[[0,0],[1,1],[2,2],[3,3]]},{"t":1489440365,"v":1,"m":2,"s":[[0,8],[1,1],[4,9],[2,2],[3,3],[5,10]]},{"t":1489440365,"v":1,"m":0,"s":[[0,11],[1,12],[2,2],[3,3]]},{"t":1489440365,"v":1,"m":3,"s":[[0,11],[1,5],[4,13],[2,14],[3,3],[5,15]]},{"t":1489440365,"v":1,"m":4,"s":[[0,11],[1,16],[2,17],[3,3],[5,10]]},{"t":1489440365,"v":1,"m":5,"s":[[0,4],[1,12],[2,18],[3,3],[6,19]]},{"t":1489440365,"v":1,"m":6,"s":[[0,8],[1,1],[2,20],[3,3],[5,21]]},{"t":1489440365,"v":1,"m":7,"s":[[0,11],[1,16],[4,22],[2,6],[3,3],[5,15]]},{"t":1489440365,"v":1,"m":0,"s":[[0,23],[1,5],[2,2],[3,3]]},{"t":1489440365,"v":1,"m":0,"s":[[0,0],[1,1],[2,2],[3,3]]},{"t":1489440365,"v":1,"m":1,"s":[[4,22]]},{"t":1489440365,"v":1,"m":5,"s":[[0,4],[1,5],[2,2],[3,3],[6,19]]},{"t":1489440365,"v":1,"m":0,"s":[[0,11],[1,12],[2,2],[3,3]]},{"t":1489440365,"v":1,"m":0,"s":[[0,24],[1,1],[2,14],[3,3]]},{"t":1489440365,"v":1,"m":0,"s":[[0,8],[1,1],[2,18],[3,3]]},{"t":1489440365,"v":1,"m":0,"s":[[0,8],[1,1],[2,14],[3,3]]},{"t":1489440365,"v":1,"m":8,"s":[[0,23],[1,16],[4,25],[2,17],[3,3],[5,15]]},{"t":1489440365,"v":1,"m":9,"s":[[0,8],[1,1],[2,6],[3,3]]},{"t":1489440365,"v":1,"m":1,"s":[[4,26]]},{"t":1489440365,"v":1,"m":0,"s":[[0,0],[1,1],[2,6],[3,3]]},{"t":1489440365,"v":1,"m":8,"s":[[0,11],[1,16],[4,22],[2,27],[3,3],[5,28]]},{"t":1489440365,"v":1,"m":0,"s":[[0,11],[1,5],[2,14],[3,3]]},{"t":1489440365,"v":1,"m":10,"s":[[0,23],[1,16],[4,29],[2,30],[3,3],[5,31]]},{"t":1489440365,"v":1,"m":11,"s":[[0,11],[1,12],[2,18],[3,3],[5,32]]},{"t":1489440365,"v":1,"m":0,"s":[[0,23],[1,5],[2,2],[3,3]]},{"t":1489440365,"v":1,"m":0,"s":[[0,4],[1,5],[2,2],[3,3]]}],"m":["Webhooks.kismia.buffer","Exim.Sent","Webhooks.kismia.sent","Webhooks.kismia.click","Webhooks.victoria.open","Webhooks.kismia.reject","Webhooks.kismia.request","Webhooks.victoria.click","Webhooks.victoria.sent","Webhooks.kismia.queue","Webhooks.dcm.click","Webhooks.kismia.open"],"c":["postmaster","lang","type","category","server","source","reason"],"s":["Mail.ru","ru","visitor","trigger","Hotmail","es","message","mail101","Yandex","mail4","hundred5_pusher","Google","pt","mail3","gift","online_pusher","en","visit","new_like","invalid","gen_reconfirm","thirty_pusher","mail108","Yahoo!","Unknown","mail104","mail109","or","hundred1_pusher","Undefined","Evening_digest_-_at_6_p_m_","today","twenty_pusher"]}';
//        $data = json_decode($data, true);

//        $eventService = new Event();
//        $added = $eventService->saveBatch($data['events'], $data['metrics'], $data['categories'], $data['slices']);

        return $this->jsonResponse(['createdEvents' => $added]);
    }
}