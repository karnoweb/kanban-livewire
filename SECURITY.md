# Security

Report vulnerabilities privately to the package owner. Do not open a public issue for an exploit.

The package escapes card text, rejects `javascript:` URLs, and refuses client-supplied scope keys. Authorization still has to be enforced again inside the host `ActionHandler`. Realtime channels must be private and authorized by the host. Broadcast payloads must stay on `RealtimeEventData`.
