<?php
// IFTTT Ubersmith Plugin Example
// Dan Brenner <dbrenner@ubersmith.com>
// JP Nacier <jnacier@ubersmith.com>

namespace DemoPlugin\Ifttt;

use UbersmithSDK\Error;
use UbersmithSDK\Parameter;
use UbersmithSDK\API;

use function UbersmithSDK\Util\I18n as I18n;

require_once 'class.client.php';

/**
 * Options array for Payload Value Config Items
 * These options will be reflected in the module config.
 * @Config value1
 * @Config value2
 * @Config value3
 */
function payload_value_options()
{
	return [
		'ip_address'   => I18n('IP Address'),
		'clientid'     => I18n('Client ID'),
		'first'        => I18n('First Name'),
		'last'         => I18n('Last Name'),
		'company'      => I18n('Company'),
		'email'        => I18n('Email'),
		'uber_login'   => I18n('Ubersmith Login Name'),
		'address'      => I18n('Address'),
		'city'         => I18n('City'),
		'state'        => I18n('State'),
		'zip'          => I18n('Zip Code'),
		'country'      => I18n('Country/Territory'),
		'phone'        => I18n('Phone'),
	];
}

/**
 * Send event after service creation
 *
 * @Hook Event\Service\AfterCreate
 * @Label Send IFTTT event after service creation
 */
function hook_ifttt_event_oncreate(Parameter\Source\Service $service, Parameter\Plugin $plugin)
{
	return send_ifttt_event($service, $plugin);
}

/**
 * Send event after service edit
 *
 * @Hook Event\Service\AfterEdit
 * @Label Send IFTTT event after service edit
 */
function hook_ifttt_event_onupdate(Parameter\Source\Service $service, Parameter\Plugin $plugin)
{
	return send_ifttt_event($service, $plugin);
}

// Send event to IFTTT
function send_ifttt_event(Parameter\Source\Service $service, Parameter\Plugin $plugin)
{
	// Set key
	$api_key = $plugin->config->ifttt_maker_key;
	if (empty($api_key)) {
		throw new Error\SDKException('No IFTTT Maker Key specified');
	}

	// Set event
	$event = $plugin->config->ifttt_maker_event;
	if (empty($event)) {
		throw new Error\SDKException('No IFTTT Maker Event specified');
	}

	// Get client details and validate
	$client = API\Client\Get([
		'client_id' => $service->clientid,
	]);
	if (Error\IsError($client)) {
		throw new Error\SDKException($client->GetMessage());
	}
	if (empty($client)) {
		throw new Error\SDKException('No client found');
	}

	// Fill values with specified client details
	$request = [];
	for ($counter = 1; $counter <= 3; $counter++) {
		$key = $plugin->config->{'value'. $counter};
		$request['value'. $counter] = empty($client[$key]) ? '' : $client[$key];
	}
	unset($key);

	// Initialize cURL client
	$curl_client = new Client();

	// Execute request to IFTTT Maker
	$url = 'https://maker.ifttt.com/trigger/'. u($event) .'/with/key/'. u($api_key);
	try {
		$result = $curl_client->send(
			$url,
			$request
		);
	} catch (\Exception $e) {
		throw new Error\SDKException('Error sending request to IFTTT. '. $e->getMessage());
	}

	// Store response
	$plugin->storage->set('response', $result);

	// Store timestamp
	$plugin->storage->set('timestamp', date('M j, Y H:i:s'));

	return true;
}

/**
 * Display IFTTT Response
 *
 * This function displays the summary output of this plugin. Any data
 * collected by your event hook and stored during that process can be
 * displayed here. In this example, we're dumping out the response
 * from IFTTT and its timestamp for your reference.
 *
 * @Hook View\Service\Summary
 * @Label IFTTT Event
 */
function ifttt_response_view(Parameter\Source\Service $service, Parameter\Plugin $plugin)
{
	// Set key
	$api_key = $plugin->config->ifttt_maker_key;
	if (empty($api_key)) {
		throw new Error\SDKException('No IFTTT Maker Key specified');
	}

	// Set event
	$event = $plugin->config->ifttt_maker_event;
	if (empty($event)) {
		throw new Error\SDKException('No IFTTT Maker Event specified');
	}

	// Get event response and timestamp for display
	$response = $plugin->storage->get('response');
	$timestamp = $plugin->storage->get('timestamp');

	$out = '<div>';
	if (empty($response)) {
		$out .= 'IFTTT Response: <span style="color: #999999;">(no response yet)</span><br><br>';
	} else {
		$out .= '<span style="font-weight: bold; color: #555555;">'. $timestamp .'</span><br>';
		$out .= '<span style="font-weight: bold; color: #555555;">IFTTT Response:</span> <span style="color: #4e4f83;">'. $response .'</span><br><br>';
	}

	// Display config
	$out .= '<span style="font-weight: bold; color: #555555;">Fields</span><br>';
	$out .= '<table>';
	$options = payload_value_options();
	for ($counter = 1; $counter <= 3; $counter++) {
		$out .= '<tr><td style="color: #666666;">';
		$out .= 'Value '. $counter .':';
		$out .= '</td><td>';
		$out .= $options[$plugin->config->{'value'. $counter}];
		$out .= '</td></tr>';
	}
	$out .= '</table><br>';

	// Convenient links to IFTTT
	$out .= '<span style="color: #4a4a4a"><a target="_blank" href="https://ifttt.com/maker_webhooks">Webhooks</a></span><br>';
	$out .= '<span style="color: #4a4a4a"><a target="_blank" href="https://ifttt.com/activity">Activity</a></span><br>';
	$out .= '<span style="color: #4a4a4a"><a target="_blank" href="https://ifttt.com/services/maker_webhooks/settings">Settings</a></span><br>';
	$out .= '</div>';

	return $out;
}
