#!/usr/bin/env python3
"""Transparent UDP relay/tunnel.

Listens for the Roblox client on 127.0.0.1:53640 (bound on all IPv4
interfaces, so it still catches loopback) and forwards every datagram to a
real game server given as "host:port" on the command line. Replies from the
server are relayed back to the client.

Usage:
    python transparent_proxy.py HOST:PORT [-v]

    HOST:PORT   address of the actual game server to forward to
    -v          print every datagram (debug)

Termux (on the same phone as the client):
    python transparent_proxy.py 192.168.215.89:2005 -v
"""

import select
import socket
import sys
import time

LISTEN_HOST = "0.0.0.0"
LISTEN_PORT = 53640
RECENT_WINDOW = 5.0


def parse_target(arg):
    host, sep, port = arg.rpartition(":")
    if not sep:
        raise SystemExit("[proxy] usage: transparent_proxy.py HOST:PORT [-v]")
    return host, int(port)


def main():
    args = [a for a in sys.argv[1:] if not a.startswith("-")]
    verbose = any(a in ("-v", "--verbose") for a in sys.argv[1:])
    if not args:
        raise SystemExit("[proxy] usage: transparent_proxy.py HOST:PORT [-v]")
    remote_host, remote_port = parse_target(args[0])

    client_sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    client_sock.bind((LISTEN_HOST, LISTEN_PORT))

    server_sock = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    server_sock.connect((remote_host, remote_port))

    if verbose:
        sys.stdout.write("[proxy] listening on 127.0.0.1:%d -> forwarding to %s:%d\n"
                         % (LISTEN_PORT, remote_host, remote_port))
        sys.stdout.flush()

    recent_clients = {}

    try:
        while True:
            readable, _, _ = select.select([client_sock, server_sock], [], [], 1.0)
            now = time.monotonic()
            for addr in list(recent_clients):
                if now - recent_clients[addr] > RECENT_WINDOW:
                    del recent_clients[addr]

            if client_sock in readable:
                data, addr = client_sock.recvfrom(65536)
                if verbose:
                    sys.stdout.write("[->] client %s:%d %d bytes\n"
                                     % (addr[0], addr[1], len(data)))
                    sys.stdout.flush()
                recent_clients[addr] = now
                server_sock.send(data)

            if server_sock in readable:
                data = server_sock.recv(65536)
                if verbose:
                    sys.stdout.write("[<-] server %s:%d %d bytes\n"
                                     % (remote_host, remote_port, len(data)))
                    sys.stdout.flush()
                for addr in list(recent_clients):
                    client_sock.sendto(data, addr)
    except KeyboardInterrupt:
        pass


if __name__ == "__main__":
    main()