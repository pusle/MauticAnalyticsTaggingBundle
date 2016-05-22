<?php

namespace MauticPlugin\MauticAnalyticsTaggingBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailBuilderEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;

/**
 * Class EmailSubscriber
 */
class EmailSubscriber extends CommonSubscriber {

    /**
     * @return array
     */
    static public function getSubscribedEvents() {
        return array(
            EmailEvents::EMAIL_ON_BUILD => array('onEmailBuild', 0),
            EmailEvents::EMAIL_ON_SEND => array('onEmailGenerate', 0),
            EmailEvents::EMAIL_ON_DISPLAY => array('onEmailGenerate', 0)
        );
    }

    /**
     * Register the tokens and a custom A/B test winner
     *
     * @param EmailBuilderEvent $event
     */
    public function onEmailBuild(EmailBuilderEvent $event) {

    }

    /**
     * Search and replace tokens with content
     *
     * @param EmailSendEvent $event
     */
    public function onEmailGenerate(EmailSendEvent $event) {

        $active = $this->factory->getParameter('active');
        if (!$active)
            return;
        // Get content
        $content = $event->getContent();
        $email = $event->getEmail();
        if (empty($email))
            return;
        $email_id = $email->getId();

        $content = str_replace('{extendedplugin}', 'world!', $content);
        $utm_campaign = $utm_source = $this->factory->getParameter('utm_source');
        $utm_content = '';
        $utm_medium = $this->factory->getParameter('utm_medium');
        $utm_campaign_type = $this->factory->getParameter('utm_campaign');
        $utm_content_type = $this->factory->getParameter('utm_content');
        $remove_accents = $this->factory->getParameter('remove_accents');


        switch ($utm_campaign_type) :
          case 'name':
            $utm_campaign = $email->getName();
            break;
          case 'subject':
            $utm_campaign = $email->getSubject();
            break;
          case 'category':
             if ( is_null($email->getCategory()) ) :
                $utm_campaign = $email->getSubject();
             else:
                $utm_campaign = $email->getCategory()->getTitle();
             endif;
            break;
        endswitch;


        switch ($utm_content_type) :
          case 'name':
            $utm_content = $email->getName();
            break;
          case 'subject':
            $utm_content = $email->getSubject();
            break;
          case 'category':
             if ( is_null($email->getCategory()) ) :
                $utm_content = $email->getSubject();
             else:
                $utm_content = $email->getCategory()->getTitle();
             endif;
            break;
        endswitch;


        if ($remove_accents) {
            setlocale(LC_CTYPE, 'en_US.UTF8');

            $str_campaign = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $utm_campaign);
            $str_campaign = str_replace(' ', '-', $str_campaign);
            $str_campaign = preg_replace('/\\s+/', '-', $str_campaign);
            $utm_campaign = strtolower($str_campaign);

            $str_content = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $utm_content);
            $str_content = str_replace(' ', '-', $str_content);
            $str_content = preg_replace('/\\s+/', '-', $str_content);
            $utm_content = strtolower($str_content);
        }

        $content = $this->add_analytics_tracking_to_urls($content, $utm_source, $utm_campaign, $utm_content, $utm_medium);
        $content = $this->add_analytics_tracking_to_urls2($content, $utm_source, $utm_campaign, $utm_content, $utm_medium);
        $event->setContent($content);
    }

    protected function add_analytics_tracking_to_urls2($body, $source, $campaign, $utem_content, $medium = 'email') {
        return preg_replace_callback('#(<v:roundrect.*?href=")([^"]*)("[^>]*?>)#i', function($match) use ($source, $campaign, $utm_content, $medium) {
            $url = $match[2];
            if (strpos($url, 'utm_source') === false && strpos($url, 'http') !== false) {

                $add_to_url = '';
                if (strpos($url, '#') !== false) {
                    $url_array = explode("#", $url);
                    if (count($url_array) == 2) {
                        $url = $url_array[0];
                        $add_to_url = '#' . $url_array[1];
                    }
                }

                if (strpos($url, '?') === false) {
                    $url .= '?';
                } else {
                    $url .= '&amp;';
                }
                $url .= 'utm_source=' . $source . '&amp;utm_medium=' . $medium . '&amp;utm_campaign=' . urlencode($campaign) . '&amp;utm_content=' . $utm_content;
                $url .=$add_to_url;
            }
            return $match[1] . $url . $match[3];
        }, $body);
    }

    protected function add_analytics_tracking_to_urls($body, $source, $campaign, $utm_content, $medium = 'email') {
        return preg_replace_callback('#(<a.*?href=")([^"]*)("[^>]*?>)#i', function($match) use ($source, $campaign, $utm_content, $medium) {
            $url = $match[2];
            if (strpos($url, 'utm_source') === false && strpos($url, 'http') !== false) {

                $add_to_url = '';
                if (strpos($url, '#') !== false) {
                    $url_array = explode("#", $url);
                    if (count($url_array) == 2) {
                        $url = $url_array[0];
                        $add_to_url = '#' . $url_array[1];
                    }
                }

                if (strpos($url, '?') === false) {
                    $url .= '?';
                } else {
                    $url .= '&amp;';
                }
                $url .= 'utm_source=' . $source . '&amp;utm_medium=' . $medium . '&amp;utm_campaign=' . urlencode($campaign) . '&amp;utm_content=' . $utm_content;
                $url .=$add_to_url;
            }
            return $match[1] . $url . $match[3];
        }, $body);
    }

}
