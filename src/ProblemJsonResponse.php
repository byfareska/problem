<?php declare(strict_types=1);

namespace Byfareska\Problem;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProblemJsonResponse extends JsonResponse
{
    private const DEFAULT_TYPES = [
        Response::HTTP_BAD_REQUEST => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.1',
        Response::HTTP_UNAUTHORIZED => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.2',
        Response::HTTP_PAYMENT_REQUIRED => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.3',
        Response::HTTP_FORBIDDEN => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.4',
        Response::HTTP_NOT_FOUND => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.5',
        Response::HTTP_METHOD_NOT_ALLOWED => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.6',
        Response::HTTP_NOT_ACCEPTABLE => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.7',
        Response::HTTP_PROXY_AUTHENTICATION_REQUIRED => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.8',
        Response::HTTP_REQUEST_TIMEOUT => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.9',
        Response::HTTP_CONFLICT => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.10',
        Response::HTTP_GONE => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.11',
        Response::HTTP_LENGTH_REQUIRED => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.12',
        Response::HTTP_PRECONDITION_FAILED => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.13',
        Response::HTTP_REQUEST_ENTITY_TOO_LARGE => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.14',
        Response::HTTP_REQUEST_URI_TOO_LONG => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.15',
        Response::HTTP_UNSUPPORTED_MEDIA_TYPE => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.16',
        Response::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.17',
        Response::HTTP_EXPECTATION_FAILED => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.18',
        Response::HTTP_I_AM_A_TEAPOT => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.19',
        Response::HTTP_MISDIRECTED_REQUEST => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.20',
        Response::HTTP_UNPROCESSABLE_ENTITY => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.21',
        Response::HTTP_UPGRADE_REQUIRED => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.5.22',
        Response::HTTP_INTERNAL_SERVER_ERROR => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.6.1',
        Response::HTTP_NOT_IMPLEMENTED => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.6.2',
        Response::HTTP_BAD_GATEWAY => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.6.3',
        Response::HTTP_SERVICE_UNAVAILABLE => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.6.4',
        Response::HTTP_GATEWAY_TIMEOUT => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.6.5',
        Response::HTTP_VERSION_NOT_SUPPORTED => 'https://datatracker.ietf.org/doc/html/rfc9110#section-15.6.6',
    ];

    public function __construct(ProblemData $data, int $status = Response::HTTP_BAD_REQUEST, array $headers = [])
    {
        parent::__construct($data, $status, $headers, false);
    }

    /**
     * @inheritDoc
     */
    protected function update(): static
    {
        $this->updateStatus();

        if (null !== $this->callback) {
            // Not using application/javascript for compatibility reasons with older browsers.
            $this->headers->set('Content-Type', 'text/javascript');

            return $this->setContent(sprintf('/**/%s(%s);', $this->callback, $this->data));
        }

        // Only set the header when there is none or when it equals 'text/javascript' (from a previous update with callback)
        // in order to not overwrite a custom definition.
        if (!$this->headers->has('Content-Type') || 'text/javascript' === $this->headers->get('Content-Type')) {
            $this->headers->set('Content-Type', 'application/problem+json');
        }

        return $this->setContent($this->data);
    }

    protected function updateStatus(): void
    {
        if ($this->data instanceof ProblemData && $this->data->getType() === null) {
            $this->data->setType(self::DEFAULT_TYPES[$this->getStatusCode()] ?? null);
        }

        if (is_array($this->data) && !isset($this->data['type'])) {
            $this->data['type'] = self::DEFAULT_TYPES[$this->getStatusCode()] ?? null;
        }

        if (is_string($this->data)) {
            try {
                $data = json_decode($this->data, true, 512, JSON_THROW_ON_ERROR);
                if (!isset($data['type'])) {
                    $type = self::DEFAULT_TYPES[$this->getStatusCode()] ?? null;

                    if ($type) {
                        $this->data = json_encode(array_merge(['type' => $type], $data), JSON_THROW_ON_ERROR);
                    }
                }
            } catch (\JsonException $e) {
            }
        }
    }
}