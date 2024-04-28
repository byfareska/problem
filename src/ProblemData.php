<?php declare(strict_types=1);

namespace Byfareska\Problem;

use JsonSerializable;
use Psr\Http\Message\UriInterface;

class ProblemData implements JsonSerializable
{
    private const RESERVED_KEYS = ['title', 'detail', 'type', 'instance'];

    public function __construct(
        /**
         * A URI reference [RFC3986] that identifies the problem type. This specification encourages that, when dereferenced,
         * it provides human-readable documentation for the problem type (e.g., using HTML [W3C.REC-html5-20141028]).
         * When this member is not present, its value is assumed to be "about:blank".
         */
        protected null|string|UriInterface $title = null,

        /**
         * A short, human-readable summary of the problem type. It SHOULD NOT change from occurrence to occurrence of the
         * problem, except for purposes of localization (e.g., using proactive content negotiation; see [RFC7231], Section 3.4).
         */
        protected null|string $detail = null,

        /**
         * A human-readable explanation specific to this occurrence of the problem.
         */
        protected null|string $type = null,

        /**
         * A URI reference that identifies the specific occurrence of the problem.
         * It may or may not yield further information if dereferenced.
         */
        protected null|string|UriInterface $instance = null,

        /**
         * Problem type definitions MAY extend the problem details object with additional members.
         */
        protected array $extensions = [],
    )
    {
    }

    public function get($key): mixed
    {
        if (in_array($key, self::RESERVED_KEYS, true)) {
            return $this->$key;
        }

        return $this->extensions[$key] ?? null;
    }

    public function jsonSerialize(): array
    {
        $data = array_filter([
            'title' => $this->title,
            'detail' => $this->detail,
            'type' => $this->type,
            'instance' => $this->instance,
        ], static fn(mixed $v): bool => $v !== null);

        $filter = static fn(string $key): bool => !in_array($key, self::RESERVED_KEYS);
        return array_merge($data, array_filter($this->extensions, $filter, ARRAY_FILTER_USE_KEY));
    }

    public function getTitle(): UriInterface|string|null
    {
        return $this->title;
    }

    public function getDetail(): ?string
    {
        return $this->detail;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getInstance(): UriInterface|string|null
    {
        return $this->instance;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }
}