# Proof-of-Concept Verifiable Credential Wordpress Plugin
This proof-of-concept plugin allows users to login to Wordpress using the Hyperledger Aries Cloudagent Python verifiable credential framework. Users need to have a credential in a mobile wallet that was issued by a specific issuer DID specified in the code (see [Variables](#variables)). With a mobile wallet, users can scan a QR code on the Wordpress login page at /wp-login.php to receive a connectionless request for proof of verifiable credential. Users then submit proof of crednetial in their mobile wallet. This plugin obtains the user's name from the proof of credential and creates an account with that name or logs the user in if an account already exists.

## Prerequisites

### Hyperledger Aries Cloudagent Python
This plugin requires a Hyperledger Aries Cloudagent Python (ACA-Py) agent running separately. This agent handles the actual verifiable proof request creation and the actual verification of the credential. This plugin interacts with the ACA-Py agent through calls to the agent's RESTful API.

In order to interact with a mobile wallet, the ACA-Py agent needs a publically addressable endpoint: see [Networking](#neworking).

For more information about setting up Hyperledger ACA-Py, see [ACAPyFramework.md](ACAPyFramework.md).

*This plugin has been tested with ACA-Py version 0.9.0*

[Hyperledger ACA-Py Repository](https://github.com/hyperledger/aries-cloudagent-python)

### Mobile Wallet
As of August 2023, this plugin works best with (and has been tested with) the [esatus](https://esatus.com/index.html%3Fp=7663&lang=en.html) mobile wallet. BC Gov has created with some other mobile wallets that may be compatible: [Getting a Mobile Wallet](https://github.com/bcgov/issuer-kit/blob/main/docs/GettingApp.md).

The user aiming to login to Wordpress with a verifiable credential must already be holding a valid verifiable credential in their mobile wallet. In order to issue a credential for development and testing purposes, use the [ACA-Py Alice Gets a Phone demo](https://github.com/hyperledger/aries-cloudagent-python/blob/main/demo/AliceGetsAPhone.md).

### Networking
The Wordpress site and the the ACA-Py agent both need to be publically addressable for the verifiable credential proof request to work. The mobile wallet needs to be able to reach both the site and the agent.

During development, [ngrok](https://ngrok.com/) can provide URLs for any publically addressable endpoints. [FRP](https://github.com/fatedier/frp) is a great open source alternative to ngrok. There are a number of other tunneling options out there. This [GitHub repository](https://github.com/anderspitman/awesome-tunneling) lists many more.

If this plugin is being run in a Docker container (for example with [wordpress/env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)), then the ACA-Py agent's admin API needs to accessible by that container. This can be accomplished by making the agent's admin API accessible to a local IP address or publically accessible. If the ACA-Py agent's admin API is publically accessible, we'd reccommend editing Controller->admin_request() in controller.php to add an API key and starting the ACA-Py agent with an API key requirement.

## Setup
This plugin acts as a "controller" for the ACA-Py agent, which means that handles the business logic for verifying credentials. In order to interact with a mobile wallet, this Wordpress page needs a publically addressable endpoint: see [Networking](#networking).

This plugin relies on a QR code library for PHP ([phpqrcode](https://github.com/giansalex/phpqrcode/tree/master)) created by giansalex on GitHub. After cloning this repository, use git submodules to setup phpqrcode.
```
$ git submodule update --init
```

After starting this plugin for the first time, the permalinks need to be updated to include the RESTful API endpoints this plugin adds to Wordpress. Login to Wordpress with an admin account and find Settings in the left sidebar. Select Permalinks and then select "Post name" as the Permalink structure. Select Save Changes at the bottom of the page. If "Post name" is already selected, still select Save Changes to update Permalinks.

Currently, this plugin provides the option for users to hardcode variables. See [Variables](#variables) for more information.

This plugin can be run with [Wordpress Env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/).

### Variables
This plugin is only a proof-of-concept, so there are some variables that can be hardcoded for customization.

Inside controller.php:
- Inside Controller->build_proof_req():
    - restrictions - It's possible to change the restrictions inside $data in build_proof_req() to change how the proof is verified. Credential defintion ID, credential definition version, schema ID, schema version, and issuer DID are all valid restrictions.

- Inside ControllerInit:
    - admin_url - This is the ACA-Py agent's admin API endpoint.
    - webhook_url - This is Wordpress site's url.
    - agent_url - This is the ACA-Py agent's endpoint.

Inside poll.js:
- increment - This the amount of time (in milliseconds) that this plugin's JavaScript frontend waits between polls to the PHP backend to check whether a credential has been verified. The default is 200 milliseconds.
- max_time - This is the amount of time (in milliseconds) until the presentation request on wp-login.php expires and the page displays a timed out message. In other words, this is amount of time the user has to scan the QR code and present a credential for logging in. The default is 60000 milliseconds.

### Troubleshooting
If there's an error displaying on /wp-login.php, double check that the ACA-Py agent is running and has been started with the appropriate parameters.

## Future Plans
This plugin is only a proof-of-concept and is not yet ready for distribution. Here is a list of to-dos to improve this plugin.
- Remove any variables that require hardcoding and make it possible to change them from the Wordpress admin page.
    - Make it possible to set restrictions on verifiable credentials for login from the Wordpress admin page.
- Add issuing a verifiable credential to the site registration process.
- Update the verifiable credential proof request to DIDComm v2.
- Improve the UI for submitting a verifiable credential proof request with a QR code.

## Licensing and Credit
This plugin is written and maintained by Janelle Krupicka, with mentorship from Maki Kato and funding from Matrix Group International. 

This code is licensed under Mozilla Public License 2.0.