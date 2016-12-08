<?php 

namespace Mayconbordin\L5StompQueue\Broadcasters;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Stomp\StatefulStomp as Stomp;
use Illuminate\Support\Arr;

class StompBroadcaster extends Broadcaster
{
    /**
     * The Stomp instance.
     *
     * @var Stomp
     */
    protected $stomp;

    /**
     * The Stomp credentials for connection.
     *
     * @var array
     */
    protected $credentials;

    /**
     * Create a Stomp Broadcaster.
     *
     * @param Stomp $stomp
     * @param array $credentials [username=string, password=string]
     */
    public function __construct(Stomp $stomp, array $credentials = [])
    {
        $this->stomp = $stomp;
        $this->credentials = $credentials;
    }

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function auth($request)
    {
        if (Str::startsWith($request->channel_name, ['private-', 'presence-']) &&
            ! $request->user()) {
            throw new HttpException(403);
        }

        $channelName = Str::startsWith($request->channel_name, 'private-')
            ? Str::replaceFirst('private-', '', $request->channel_name)
            : Str::replaceFirst('presence-', '', $request->channel_name);

        return parent::verifyUserCanAccessChannel(
            $request, $channelName
        );
    }

    /**
     * Return the valid authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return mixed
     */
    public function validAuthenticationResponse($request, $result)
    {
        if (is_bool($result)) {
            return json_encode($result);
        }

        return json_encode(['channel_data' => [
            'user_id' => $request->user()->getKey(),
            'user_info' => $result,
        ]]);
    }

    /**
     * Broadcast the given event.
     *
     * @param  array $channels
     * @param  string $event
     * @param  array $payload
     * @return void
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $this->connect();

        $payload = json_encode(['event' => $event, 'data' => $payload]);

        foreach ($channels as $channel) {
            $this->stomp->send($channel, $payload);
        }
    }

    /**
     * Connect to Stomp server, if not connected.
     *
     * @throws \Stomp\Exception\StompException
     * @throws \Stomp\Exception\StompException
     */
    protected function connect()
    {
        if (!$this->stomp->isConnected()) {
            $this->stomp->connect(Arr::get($this->credentials, 'username', ''), Arr::get($this->credentials, 'password', ''));
        }
    }
}
