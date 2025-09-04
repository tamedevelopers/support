# HTTP Layer (Tamedevelopers\Support\Process)

This directory contains lightweight, framework‑agnostic HTTP contracts and native implementations.

Interfaces:
- RequestInterface (RequestInterface.php)
- SessionInterface (SessionInterface.php)

Implementations:
- NativeRequest: works on PHP superglobals
- NativeSession: wraps PHP session_* APIs

Note: The legacy Contracts.php combined interfaces are superseded by the separate interface files.