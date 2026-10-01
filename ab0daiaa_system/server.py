import http.server, socketserver, subprocess, os

class PHPHandler(http.server.BaseHTTPRequestHandler):
    def do_GET(self): self.run_php()
    def do_POST(self): self.run_php()
    def run_php(self):
        path = self.path.split('?')[0]
        if path == '/': path = '/pos_touch.php'
        script_file = os.path.join(os.getcwd(), path.lstrip('/'))
        
        if not os.path.exists(script_file) or not script_file.endswith('.php'):
            self.send_error(404, "Not Found")
            return

        env = os.environ.copy()
        env['REQUEST_METHOD'] = self.command
        env['SCRIPT_FILENAME'] = script_file
        env['REDIRECT_STATUS'] = '200'
        env['QUERY_STRING'] = self.path.split('?', 1)[1] if '?' in self.path else ''
        env['CONTENT_TYPE'] = self.headers.get('Content-Type', '')
        env['CONTENT_LENGTH'] = self.headers.get('Content-Length', '')
        if 'Cookie' in self.headers: env['HTTP_COOKIE'] = self.headers.get('Cookie', '')

        post_data = None
        if self.command == 'POST' and env['CONTENT_LENGTH']:
            post_data = self.rfile.read(int(env['CONTENT_LENGTH']))

        try:
            proc = subprocess.Popen(['php-cgi'], env=env, stdin=subprocess.PIPE, stdout=subprocess.PIPE)
            out, _ = proc.communicate(input=post_data)
            
            head, body = out.split(b'\r\n\r\n', 1) if b'\r\n\r\n' in out else (out, b'')
            
            headers = {}
            status = 200
            for line in head.split(b'\r\n'):
                if b':' in line:
                    k, v = [x.strip().decode('utf-8', 'ignore') for x in line.split(b':', 1)]
                    headers[k] = v
                    if k.lower() == 'status': status = int(v.split(' ')[0])
                    elif k.lower() == 'location': status = 302
            
            self.send_response(status)
            for k, v in headers.items():
                if k.lower() != 'status': self.send_header(k, v)
            self.end_headers()
            self.wfile.write(body)
        except Exception as e:
            self.send_error(500, str(e))

socketserver.TCPServer.allow_reuse_address = True
httpd = socketserver.TCPServer(("127.0.0.1", 8888), PHPHandler)
print("✅ السيرفر يعمل بنجاح! الرابط: http://127.0.0.1:8888")
httpd.serve_forever()
