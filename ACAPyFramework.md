## Hyperledger Aries Cloud Agent Python

[Hyperledger ACA-Py Repository](https://github.com/hyperledger/aries-cloudagent-python)

### What is Hyperledger Aries?
[Hyperledger Aries](https://www.hyperledger.org/projects/aries) is an open-source toolkit for decentralized identity, which includes verifiable credentials. It defines the functionalities that are then implemented by specific frameworks, like Aries Cloud Agent Python (ACA-Py).

Hyperledger Aries provides the protocols necessary to put users in control of their own data. By using verifiable credentials to prove identity and/or authority, users can choose when to share their information, and can confirm that they trust who they are sharing it with. When a verifiable credential is issued to an individual, organization, IoT device, etc., the issuer cryptographically signs the credential and it is then bound to the holder. When that holder needs to prove their identity and/or authority, the entity verifying their credential can be confident that it was issued by a trusted issuer through the cryptographic signature without checking back with that issuer. The ability to confirm identity information without checking back with the source is what makes verifiable credentials decentralized.

There are numerous use cases for verifiable credentials. Verifiable credentials can drastically reduce fraud, make online logins more convenient, and prevent companies from sharing personal information. This Wordpress plugin makes logging into Wordpress more convenient and puts the user in charge of their experience. Rather than needing to remember a username and password, users just need a verifiable credential issued by a trusted issuer to login. This verifiable credential could be used for more than just logging in. For example, say your Wordpress site organizes your university alumni. In order to login to this site, you would just need to present a degree credential issued by your university. You could use that same credential to give proof of education to your employer. Neither the alumni website nor your employer would need to check with your university to verify your degree, simplifying the process.

Hyperledger Aries is an open-source community and set of standards for issuing, verifying, and revoking verifiable credentials (along with many other facets of decentralized identity). This Wordpress plugin uses Hyperledger Aries to handle verifying a credential for logging into Wordpress.

Here is a [Medium article about verifiable credentials](https://academy.affinidi.com/what-are-verifiable-credentials-79f1846a7b9) for more information.

### What is an ACA-Py agent?
Hyperledger Aries defines "agents" that carry out actions related to verifiable credentials and decentralized identity in general. While these agents are running, they're accessible via their RESTful API. 

Aries Cloud Agent Python (ACA-Py) is an Aries agent written in Python for non-mobile environments. This Wordpress plugin requires an ACA-Py agent running on a server of some kind that is reachable by the Wordpress site instance. That ACA-Py agent has an admin API that this plugin calls to create a proof of credential request and then to verify the credential information (also known as the "presentation") that it receives from the mobile wallet containing the credential.

## Setting up ACA-Py
Check out this [ACA-Py DevReadMe file](https://github.com/hyperledger/aries-cloudagent-python/blob/main/DevReadMe.md) for information about how to set up the ACA-Py agent. It's possible to download ACA-Py from PyPi to run or to clone the ACA-Py repo and run an ACA-Py agent in a Docker container. The DevReadMe file also provides more information about arguments to run the ACA-Py agent with.

#### Choosing a Ledger
Choose a ledger that you're able to endorse a DID to. You can use the [BCovrin Test Net](http://test.bcovrin.vonx.io/) for testing and development. You specify the ledger by running the ACA-Py agent with the genesis-url argument specified. In the case of BCovrin Test Net, the genesis url is http://test.bcovrin.vonx.io/genesis.

#### Setting up DID
One of the required arguments is a DID seed. The ACA-Py agent creates a DID using the sov method on default, but you must manually register that DID to the ledger you're using. By specifying a seed, you're able to register a DID to the ledger before starting the ACA-Py agent. The seed must be 32 characters or base64. Register using that seed before starting the ACA-Py agent.

#### Endpoints
The ACA-Py agent's endpoint is its publically accessible address for receiving a verifiable presentation from a mobile agent. Run the ACA-Py agent with "endpoint" specified in its arguments.

The Wordpress site has a publically accessible address for managing requests from the mobile wallet and for getting updates from the ACA-Py agent. The site's url is set with "webhook-url" in the ACA-Py agent's arguments.

See the [Networking](README.md#networking) section in the README for more information about setting up these endpoints for development.

#### Args list
In order for this Wordpress plugin to work, the ACA-Py agent must be run with these arguments specified. They can be set in a .yml file that the agent can access (if the agent is running inside a Docker container, be sure to add this file to that container). These arguments can also be specified from the command line when running the agent.

The ports for inbound-transport and outbound-transport can be changed. Endpoint, webhook-url, genesis-url, and seed all need to be filled in. Admin-insecure-mode should only be used for development and can be replaced by admin-api-key for more secure access to the agent's admin API.

```
endpoint: 
inbound-transport: 
  - [http, 0.0.0.0, 8030]
outbound-transport: http
admin: [0.0.0.0, 8031]
admin-insecure-mode: true
webhook-url: 
wallet-type: askar
auto-provision: true
genesis-url: 
seed: 
```

#### Running the Agent
If you're running the ACA-Py agent in a Docker container, navigate to the aries-cloudagent-python directory and run the folllowing. If you changed the ports for inbound and outbound transport, update the ports to reflect that. 

```
PORTS="8030:8030 8031:8031" scripts/run_docker start --arg-file your-args.yml
```

A banner should print to your console with the agent's inbound transports, outbound transports, public DID information, and administration API. 

If you're running the ACA-Py agent using the aca-py executable, follow the instructions in the [DevReadMe file](https://github.com/hyperledger/aries-cloudagent-python/blob/main/DevReadMe.md) file to run the agent.

### Testing an ACA-Py agent
Here are a few methods for interacting with an ACA-Py agent's admin API to check that it's working.
- Making a GET request to /wallet/did/public should return a JSON object containing a DID that aligns with the seed you used, a verkey, the key_type, and the method.
- Making a GET request to /ledger/did-endpoint should return the endpoint that was set in your agent's arguments.
- Sending a POST request to /connections/create-invitation with this data:
}
  "metadata": {},
  "my_label": "Bob",
  "recipient_keys": [
    "H3C2AVvLMv6gmMNam3uVAjZpfkcJCwDwnZn6z3wXmqPV"
  ],
  "routing_keys": [
    "H3C2AVvLMv6gmMNam3uVAjZpfkcJCwDwnZn6z3wXmqPV"
  ],
  "service_endpoint": "http://192.168.56.102:8020"
}
should return something like this (the connection id and invitation url will be different):
{ "connection_id": "68ac3867-30a9-4743-8c3f-392ad8509f1a", "invitation": { "@type": "did:sov:BzCbsNYhMrjHiqZDTUASHg;spec/connections/1.0/invitation", "@id": "262a7e02-ab63-43d9-9efc-ec33f0401b1b", "recipientKeys": [ "H3C2AVvLMv6gmMNam3uVAjZpfkcJCwDwnZn6z3wXmqPV" ], "serviceEndpoint": "http://192.168.56.102:8020", "label": "Bob" }, "invitation_url": "http://192.168.56.102:8020?c_i=eyJAdHlwZSI6ICJkaWQ6c292OkJ6Q2JzTlloTXJqSGlxWkRUVUFTSGc7c3BlYy9jb25uZWN0aW9ucy8xLjAvaW52aXRhdGlvbiIsICJAaWQiOiAiMjYyYTdlMDItYWI2My00M2Q5LTllZmMtZWMzM2YwNDAxYjFiIiwgInJlY2lwaWVudEtleXMiOiBbIkgzQzJBVnZMTXY2Z21NTmFtM3VWQWpacGZrY0pDd0R3blpuNnozd1htcVBWIl0sICJzZXJ2aWNlRW5kcG9pbnQiOiAiaHR0cDovLzE5Mi4xNjguNTYuMTAyOjgwMjAiLCAibGFiZWwiOiAiQm9iIn0=" }

## What is a connectionless proof?
Typically, issuing and verifying credentials require establishing a connection between the two parties interacting before carrying out the interaction. Using [DIDComm v1](https://github.com/hyperledger/aries-rfcs/blob/main/concepts/0005-didcomm/README.md), it's possible to make a request for proof to a mobile wallet without connecting to that wallet first. This makes it easy to send proof of credential by simply scanning a QR code from your mobile wallet. In the future, DIDComm v2 will not support connectionless proofs in the same way. This Wordpress plugin uses DIDComm v1 and will eventually need to be updated to reflect DIDComm v2.
