<?php
/**
 * @brief		tfMatrixChatWhosOnline Widget
 * @author		<a href='https://www.tolkienforum.de'>TolkienForum.De</a>
 * @copyright	(c) 2022 TolkienForum.De
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	tfmatrixchat
 * @since		05 Jan 2022
 */

namespace IPS\tfmatrixchat\widgets;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * tfMatrixChatWhosOnline Widget
 */
class _tfMatrixChatWhosOnline extends \IPS\Widget
{
	/**
	 * @brief	Widget Key
	 */
	public $key = 'tfMatrixChatWhosOnline';

    protected $cacheKey = '';
    protected $cacheExpiration = 60;

	/**
	 * @brief	App
	 */
	public $app = 'tfmatrixchat';

	/**
	 * Constructor
	 *
	 * @param	String				$uniqueKey				Unique key for this specific instance
	 * @param	array				$configuration			Widget custom configuration
	 * @param	null|string|array	$access					Array/JSON string of executable apps (core=sidebar only, content=IP.Content only, etc)
	 * @param	null|string			$orientation			Orientation (top, bottom, right, left)
	 * @return	void
	 */
	public function __construct( $uniqueKey, array $configuration, $access=null, $orientation=null )
	{
		parent::__construct( $uniqueKey, $configuration, $access, $orientation );

		$this->neverCache = TRUE;
		$this->cacheKey = "widget_{$this->key}_" . $this->uniqueKey . '_' . md5( json_encode( $configuration ) . "_" . \IPS\Member::loggedIn()->language()->id );
	}

	public function init()
	{
		// Use this to perform any set up and to assign a template that is not in the following format:
		// $this->template( array( \IPS\Theme::i()->getTemplate( 'widgets', $this->app, 'front' ), $this->key ) );
		// If you are creating a plugin, uncomment this line:
		// $this->template( array( \IPS\Theme::i()->getTemplate( 'plugins', 'core', 'global' ), $this->key ) );
		// And then create your template at located at plugins/<your plugin>/dev/html/tfMatrixChatWhosOnline.phtml
		parent::init();

		// include css for widgets:
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css('styles.css', $this->app, 'front'));

		$this->template( array( \IPS\Theme::i()->getTemplate( 'widgets', $this->app, 'front' ), $this->key ) );
	}
	
	/**
	 * Specify widget configuration
	 *
	 * @param	null|\IPS\Helpers\Form	$form	Form object
	 * @return	null|\IPS\Helpers\Form
	 */
	public function configuration( &$form=null )
	{
 		$form = parent::configuration( $form );

		$form->add(new \IPS\Helpers\Form\Text('tfmatrixchat_url', isset( $this->configuration['tfmatrixchat_url'] ) ? $this->configuration['tfmatrixchat_url'] : ''));
		$form->add(new \IPS\Helpers\Form\Text('tfmatrixchat_token', isset( $this->configuration['tfmatrixchat_token'] ) ? $this->configuration['tfmatrixchat_token'] : ''));
        $form->add(new \IPS\Helpers\Form\Text('tfmatrixchat_room_id', isset( $this->configuration['tfmatrixchat_room_id'] ) ? $this->configuration['tfmatrixchat_room_id'] : ''));
        $form->add(new \IPS\Helpers\Form\Text('tfmatrixchat_filter_usernames', isset( $this->configuration['tfmatrixchat_filter_usernames'] ) ? $this->configuration['tfmatrixchat_filter_usernames'] : ''));

 		return $form;
 	} 
 	
 	 /**
 	 * Ran before saving widget configuration
 	 */
 	public function preConfig( $values )
 	{
 		return $values;
 	}

	public function render()
	{
		/* Do we have permission? */
		if ( !\IPS\Member::loggedIn()->canAccessModule( \IPS\Application\Module::get( 'core', 'online' ) ) )
		{
			return "";
		}

		if(!isset($this->configuration['tfmatrixchat_url'])
			|| !isset($this->configuration['tfmatrixchat_token']))
		{
			return \IPS\Member::loggedIn()->language()->addToStack("tfmatrixchat_no_settings");
		}

		/* Does this go in the store? Things like active users don't get stored, and if in developer or designer mode, nothing does */
		if ( isset( $this->cacheKey ) AND !\IPS\IN_DEV AND !\IPS\Theme::designersModeEnabled() )
		{
			$cacheKey = $this->cacheKey;
			$expiration = $this->cacheExpiration;
			// initialize with empty cache structure if non-existing
			if ( !isset( \IPS\Data\Store::i()->$cacheKey ) )
			{
				\IPS\Data\Store::i()->$cacheKey = array( 'built' => 0, 'html' => '' );
			}

			// check if cache content needs to be refreshed
			$widget = \IPS\Data\Store::i()->$cacheKey;
			if( (int)$widget['built'] + $expiration < time() )
			{
				// \IPS\Log::log('refreshing matrix chat online users, cacheKey: ' . $cacheKey, 'tfmatrixchat');
                $content = $this->createHtml();
                \IPS\Member::loggedIn()->language()->parseOutputForDisplay( $content );
				$widget = array( 'built' => time(), 'html' => $content );

				\IPS\Data\Store::i()->$cacheKey = $widget;
			}

			return $widget['html'];
		}
		else
		{
			\IPS\Log::log('not using cache (IN_DEV or designersMode).', 'tfmatrixchat');
			return $this->createHtml();
		}
	}

	protected function createHtml() {
        $url = $this->configuration['tfmatrixchat_url'];
        $token = $this->configuration['tfmatrixchat_token'];
        $roomId = $this->configuration['tfmatrixchat_room_id'];

        $hideUsernames = array();
        if(array_key_exists('tfmatrixchat_filter_usernames', $this->configuration)) {
            $hideUsernamesString = $this->configuration['tfmatrixchat_filter_usernames'];
            $hideUsernames = explode(",", $hideUsernamesString);
        }

        //$roomId = "!VtFPkXghMxFBzFquhx:matrix.tolkienforum.de";

        $chatVersionDefault = array(
            'name' => "?",
            'version' => "0"
        );
        $topic = "";
        $orientation = $this->orientation;

        // read server name and version: /_matrix/federation/v1/version
        $chatVersion = $this->readJsonFromUrl($url . '/_matrix/federation/v1/version', $chatVersionDefault)['server'];

        // read users display names: /_matrix/client/v3/rooms/{{roomId}}/joined_members?access_token={{token}}
        $getParams = array(
            'access_token' => $token
        );

        $displayNamesResp = $this->readJsonFromUrl($url . "/_matrix/client/v3/rooms/$roomId/joined_members", $getParams);

        $matrixUserIdToDisplayName = array();
        foreach($displayNamesResp['joined'] as $matrixUserId => $userProps) {
            $matrixUserIdToDisplayName += [$matrixUserId => $userProps['display_name']];
        }

        // read presence: GET {{host}}/_matrix/client/v3/sync?access_token={{token}}&filter={"account_data": {"not_types": ["*"]}, "presence": {"limit": 1}, "room":{"rooms": ["!VtFPkXghMxFBzFquhx:matrix.tolkienforum.de"], "timeline":{"limit":1}, "state":{ "types": ["m.room.topic"]}}}
        $getParams = array(
            'access_token' => $token,
            'filter' => '{"account_data": {"not_types": ["*"]}, "presence": {"limit": 1}, "room":{"rooms": ["' . $roomId . '"], "timeline":{"limit":1}, "state":{ "types": ["m.room.topic"]}}}'
        );

        $presenceResp = $this->readJsonFromUrl($url . '/_matrix/client/v3/sync', $getParams);
        $onlineUsers = $presenceResp['presence']['events'];

        $members = array();
        $validStatus = array("online");
        foreach($onlineUsers as $user) {
            $matrixUserId = $user['sender'];
            $status = $user['content']['presence']; // should be 'online' for now?
            $forumUserName = $matrixUserIdToDisplayName[$matrixUserId];

            // only add online users and not hidden by configuration:
            if(\in_array($status, $validStatus) && !\in_array($forumUserName, $hideUsernames)) {
                $member = \IPS\Member::load($forumUserName, 'name');
                array_push($members, array(
                        "name" => $forumUserName,
                        "username" => $forumUserName,
                        "status" => $status,
                        "forumUserID" => $member->member_id,
                        "seo_name" => $member->members_seo_name,
                        "groupID" => $member->member_group_id
                    )
                );
            }
        }

        $memberCount = \count($members);

        if(isset($presenceResp['rooms']['join'][$roomId]['state']['events'][0]['content']['topic'])) {
            $topic = $presenceResp['rooms']['join'][$roomId]['state']['events'][0]['content']['topic'];
        }

        if(isset($presenceResp['rooms']['join'][$roomId]['timeline']['events'][0]['content']['topic'])) {
            $topic = $presenceResp['rooms']['join'][$roomId]['timeline']['events'][0]['content']['topic'];
        }

        return $this->output($members, $memberCount, $chatVersion, $topic, $orientation);
	}

    protected function readJsonFromUrl($url, $urlParams=array(), $defaultReturn=array()) {
        $opts = array(
            'http' => array(
                'method' => "GET",
                'header' => "Accept: application/json",
                'timeout' => 3
            )
        );
        $context = stream_context_create($opts);

        $requestUrl = $url . "?" . http_build_query($urlParams, '', '&amp;');

        try {
            $response = file_get_contents($requestUrl, false, $context);
            if($response === false)	{
                throw new \Exception('Could not read from (null): ' . $url);
            }
            $arr = json_decode($response, true);
            return $arr;

        } catch(\Exception $ex) {
            \IPS\Log::log('Could not read from: ' . $url , 'tfmatrixchat');
            \IPS\Log::log('Exception while calling file_get_contents: ' . $ex->getMessage() , 'tfmatrixchat');
//			\IPS\Log::log('Trace: ' . $ex->getTraceAsString() , 'tfmatrixchat');
//			\IPS\Log::log('Returning defaults: ' . print_r($defaultReturn, true));

            return $defaultReturn;
        }
    }


}