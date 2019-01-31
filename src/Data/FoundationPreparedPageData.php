<?php

declare(strict_types=1);

namespace Capell\FoundationTheme\Data;

use Capell\Core\Models\Page;
use Capell\Frontend\Contracts\FrontendContextReader;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/** Only the two explicitly prepared results cross the event snapshot boundary. */
final readonly class FoundationPreparedPageData
{
    /**
     * @param  array<string, array{class: class-string<Model>, key: int|string, connection: ?string}>  $identity
     * @param  Collection<int, Page>|null  $ancestors
     */
    private function __construct(
        private array $identity,
        public bool $contactPrepared,
        public ?Page $contactPage,
        public bool $ancestorsPrepared,
        public ?Collection $ancestors,
    ) {}

    public static function fromContext(FrontendContextReader $context): ?self
    {
        $identity = self::identity($context);
        $data = $context->getFrontendData();
        if ($identity === null || ! is_array($data)) {
            return null;
        }

        $contact = $data['foundation.footer.contact_page'] ?? null;
        $ancestors = $data['foundation.page.ancestors'] ?? null;
        if ($contact !== null && ! $contact instanceof Page) {
            return null;
        }

        $preparedAncestors = null;
        if ($ancestors !== null) {
            if (! $ancestors instanceof Collection) {
                return null;
            }

            /** @var Collection<int, Page> $preparedAncestors */
            $preparedAncestors = new Collection;
            foreach ($ancestors as $ancestor) {
                if (! $ancestor instanceof Page) {
                    return null;
                }
                $preparedAncestors->push($ancestor);
            }
        }

        return new self(
            identity: $identity,
            contactPrepared: array_key_exists('foundation.footer.contact_page', $data),
            contactPage: $contact,
            ancestorsPrepared: array_key_exists('foundation.page.ancestors', $data),
            ancestors: $preparedAncestors,
        );
    }

    public function transferTo(FrontendContextReader $context): void
    {
        if ($this->identity !== self::identity($context)) {
            return;
        }

        if ($this->contactPrepared) {
            $context->setFrontendData('foundation.footer.contact_page', $this->contactPage);
        }

        if ($this->ancestorsPrepared) {
            $context->setFrontendData('foundation.page.ancestors', $this->ancestors);
        }
    }

    /** @return array<string, array{class: class-string<Model>, key: int|string, connection: ?string}>|null */
    private static function identity(FrontendContextReader $context): ?array
    {
        $identity = [];
        foreach (['page' => $context->page(), 'site' => $context->site(), 'language' => $context->language()] as $name => $model) {
            if (! $model instanceof Model) {
                return null;
            }

            $key = $model->getKey();
            if (! is_int($key) && ! is_string($key)) {
                return null;
            }

            $identity[$name] = ['class' => $model::class, 'key' => $key, 'connection' => $model->getConnection()->getName()];
        }

        return $identity;
    }
}
