<?php declare(strict_types=1);

namespace Byfareska\Problem;

use JsonSerializable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ProblemJsonResponse extends JsonResponse
{
    public function __construct(ProblemData $data, int $status = Response::HTTP_BAD_REQUEST, array $headers = [])
    {
        parent::__construct($data, $status, $headers, false);
    }

    /**
     * @inheritDoc
     */
    protected function update(): static
    {
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
}