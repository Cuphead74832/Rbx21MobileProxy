from mitmproxy import http

def request(flow: http.HTTPFlow) -> None:
    local_host = "localhost"
    local_port = 8081

    if "assetdelivery.roblox.com" in flow.request.host:
        return

    if not (flow.request.host == "roblox.com" or flow.request.host.endswith(".roblox.com")):
        return

    # Save the original host
    original_host = flow.request.host

    # Redirect all requests while preserving the Host header
    flow.request.host = local_host
    flow.request.port = local_port
    flow.request.scheme = "http"  # Assuming localhost does not use HTTPS

    # Preserve the original Host header
    flow.request.headers["Host"] = original_host
