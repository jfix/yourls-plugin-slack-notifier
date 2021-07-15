<?php
/*
Plugin Name: Slack Notifier
Plugin URI: https://github.com/jfix/yourls-plugin-slack-notifier
Description: Get a Slack notification each time someone registers a URL
Version: 1.0
Author: Jakob Fix
Author URI: https://github.com/jfix
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

// associate the "post_add_new_link" with the "jfix_notify_slack" function
yourls_add_action( 'post_add_new_link', 'jfix_notify_slack' );

function jfix_notify_slack( $args ) {

    // get the info we want to send to Slack
    $techInfo = $args[3];
    $url = $args[0];
    $keyword = $args[1];
    $shortURL = YOURLS_SITE . "/" . $keyword;
    // no guarantee the page title is provided
    $title = empty($args[2]) ? $shortURL : $args[2];
    $ip = $techInfo['url']['ip'];
    // timestamp as required by Slack
    $date = strtotime($techInfo['url']['date']);
    // fallback date time string
    $formattedDate = date("j F Y, G:i:s", $date);

    // prepare the Slack-specific parameters
    $slackWebhook = "__SLACK_WEBHOOK__";
    $opts = [
        "Content-type: application/json"
    ];

    // documentation here: https://app.slack.com/block-kit-builder/
    $contents = <<<EOS
    {
        "blocks": [
            {
                "type": "section",
                "text": {
                    "type": "mrkdwn",
                    "text": "📢 Somebody just registered a short URL. The keyword is *$keyword* and the URL $url. Below are some more details:"
                }
            },
            {
                "type": "section",
                "fields": [
                    {
                        "type": "mrkdwn",
                        "text": "📔 *Page title*:\n$title"
                    },
                    {
                        "type": "mrkdwn",
                        "text": "🌎 *URL*:\n$url"
                    },
                    {
                        "type": "mrkdwn",
                        "text": "🪄 *Keyword:* `$keyword`"
                    },
                    {
                        "type": "mrkdwn",
                        "text": "🖥 *IP address of creator*: $ip"
                    },
                    {
                        "type": "mrkdwn",
                        "text": "📅 *Date time*: <!date^$date^{date_short} {time}|$formattedDate UTC>"
                    },
                    {
                        "type": "mrkdwn",
                        "text": "📈 *Statistics*: <$shortURL+|oe.cd/$keyword+>"
                    },
                ]
            }
        ]
    }
    EOS;

    // for debugging
    // error_log(print_r($args, true));

    // send the POST request to Slack
    yourls_http_post_body($slackWebhook, $opts, $contents);
}

/*
$args contains this array:

Array
(
    [0] => https://www.google.com/
    [1] => theKeyWord
    [2] => Google Search
    [3] => Array
        (
            [url] => Array
                (
                    [keyword] => theKeyWord
                    [url] => https://www.google.com/
                    [title] => Google Search
                    [date] => 2021-07-12 12:48:10
                    [ip] => ::1
                )

            [status] => success
            [message] => https://www.google.com/ added to database
            [title] => Google Search
            [html] => a DOM element, table row to be inserted in the admin table maybe?
            [shorturl] => http://localhost:8080/yourls/theKeyWord
        )
)
 */