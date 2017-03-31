<?php

/**
 * @author Kirk Mayo <kirk.mayo@solnet.co.nz>
 *
 * A controller class for handling API requests
 */
class SocialAPI extends Controller
{
    private static $allowed_actions = array(
        'countsfor'
    );

    private static $url_handlers = array(
        'countsfor/$SERVICE/$FILTER'
        => 'countsFor'
    );

    public function countsFor()
    {
        $response = $this->getResponse();
        $cors = Config::inst()->get('SocialAPI', 'CORS');

        if ($cors) {
            $response->addHeader('Access-Control-Allow-Origin', '*');
        }

        $urls = explode(',', $this->request->getVar('urls'));
        // queue all urls to be checked
        foreach ($urls as $url) {
            $result = SocialQueue::queueURL($url);
        }
        $urlObjs = URLStatistics::get()
            ->filter(array(
                'URL' => $urls
            ));
        if (!$urlObjs->count()) {
            $response->setBody(json_encode(array()));

            return $response;
        }
        $results = array();
        // do we need to filter the results any further
        $service = $this->getRequest()->param('SERVICE');
        $filter = null;
        if ($service && $service == 'service') {
            $filter = $this->getRequest()->param('FILTER');
        }
        foreach ($urlObjs as $urlObj) {

            if (!isset($results['results'][$urlObj->URL]['Total'])) {
                $results['results'][$urlObj->URL]['Total'] = 0;
            }

            if ($filter) {
                if (strtoupper($urlObj->Service) == strtoupper($filter)) {
                    $results['results'][$urlObj->URL]['Total'] += $urlObj->Count;
                    $results['results'][$urlObj->URL][$urlObj->Service][] = array(
                        $urlObj->Action => $urlObj->Count
                    );
                }
            } else {
                $results['results'][$urlObj->URL]['Total'] += $urlObj->Count;
                $results['results'][$urlObj->URL][$urlObj->Service][] = array(
                    $urlObj->Action => $urlObj->Count
                );
            }
        }

        $response->setBody(json_encode($results));

        return $response;
    }
}
