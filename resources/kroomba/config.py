class Config(object):
    def __init__(self, **kwargs):
        self._kwargs = kwargs

    @property
    def host(self):
        return self._kwargs.get('host', '127.0.0.1')

    @property
    def port(self):
        return self._kwargs.get('port', 1883)

    @property
    def user(self):
        return self._kwargs.get('user', '')

    @property
    def password(self):
        return self._kwargs.get('password', '')

    @property
    def topic_prefix(self):
        return self._kwargs.get('topic_prefix', 'kroomba')

    @property
    def excluded_blid(self):
        blids = self._kwargs.get('excluded_blid', '')
        return [str(x) for x in blids.split(',') if x != '']

    @property
    def apiKey(self):
        return self._kwargs.get('apikey', '')

    @property
    def callbackUrl(self):
        return self._kwargs.get('callback', '')

    @property
    def socketport(self):
        return self._kwargs.get('socketport', 55072)
