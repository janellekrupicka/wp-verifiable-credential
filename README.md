## VC Wordpress Plugin

## Description
This plugin allows users to login to Wordpress using the Hyperledger Aries Cloudagent Python verifiable credential framework. Users need to have a credential in a mobile wallet that was issued by a specific issuer DID specified in the code (see [Variables](#variables)). With a mobile wallet, users can scan a QR code on the Wordpress login page at /wp-login.php to receive a connectionless request for proof of verifiable credential. Users then submit proof of crednetial in their mobile wallet. This plugin obtains the user's name from the proof of credential and creates an account with that name or logs the user in if an account already exists.

## Hyperledger Aries Cloudagent Python Dependency
This plugin requires a Hyperledger Aries Cloudagent Python (ACA-Py) agent running separately. This agent handles the actual verifiable proof request creation and the actual verification of the credential. This plugin interacts with the ACA-Py agent through calls to the agent's RESTful API.

In order to interact with a mobile wallet, the ACA-Py agent needs a publically addressable endpoint: see [Networking](#neworking).

[Hyperledger ACA-Py Repository](https://github.com/hyperledger/aries-cloudagent-python)

## Setup
This plugin acts as a "controller" for the ACA-Py agent, which means that handles the business logic for verifying credentials. In order to interact with a mobile wallet, this Wordpress page needs a publically addressable endpoint: see [Networking](#networking).

This plugin can be run with [Wordpress Env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/).

## Variables

Inside vc-login.php:
- admin_url - This is the ACA-Py agent's admin API endpoint.
- webhook_url - This is Wordpress site's url.
- agent_url - This is the ACA-Py agent's endpoint.

Inside controller.php:
- restrictions - It's possible to change the restrictions inside $data in build_proof_req() to change how the proof is verified. Credential defintion ID, credential definition version, schema ID, schema version, and issuer DID are all valid restrictions.

Inside poll.js:
- increment - This the amount of time (in milliseconds) that this plugin's JavaScript frontend waits between polls to the PHP backend to check whether a credential has been verified.
- max_time - This is the amount of time (in milliseconds) until the presentation request on wp-login.php expires and the page displays a timed out message.

## Networking