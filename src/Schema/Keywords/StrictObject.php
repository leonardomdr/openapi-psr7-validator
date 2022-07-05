<?php

declare(strict_types=1);

namespace League\OpenAPIValidation\Schema\Keywords;

use cebe\openapi\spec\Schema as CebeSchema;
use League\OpenAPIValidation\Schema\BreadCrumb;
use League\OpenAPIValidation\Schema\Exception\InvalidSchema;
use League\OpenAPIValidation\Schema\Exception\KeywordMismatch;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use Respect\Validation\Exceptions\Exception;
use Respect\Validation\Exceptions\ExceptionInterface;
use Respect\Validation\Validator;

use function array_keys;
use function sprintf;

class StrictObject extends BaseKeyword
{
    /** @var int this can be Validator::VALIDATE_AS_REQUEST or Validator::VALIDATE_AS_RESPONSE */
    protected $validationDataType;
    /** @var BreadCrumb */
    protected $dataBreadCrumb;

    public function __construct(CebeSchema $parentSchema, int $type, BreadCrumb $breadCrumb)
    {
        parent::__construct($parentSchema);
        $this->validationDataType = $type;
        $this->dataBreadCrumb     = $breadCrumb;
    }

    /**
     * Property definitions MUST be a Schema Object and not a standard JSON Schema (inline or referenced).
     * If absent, it can be considered the same as an empty object.
     *
     *
     * Value can be boolean or object.
     * Inline or referenced schema MUST be of a Schema Object and not a standard JSON Schema.
     * Consistent with JSON Schema, additionalProperties defaults to true.
     *
     * The value of "additionalProperties" MUST be a boolean or a schema.
     *
     * If "additionalProperties" is absent, it may be considered present
     * with an empty schema as a value.
     *
     * If "additionalProperties" is true, validation always succeeds.
     *
     * If "additionalProperties" is false, validation succeeds only if the
     * instance is an object and all properties on the instance were covered
     * by "properties" and/or "patternProperties".
     *
     * If "additionalProperties" is an object, validate the value as a
     * schema to all of the properties that weren't validated by
     * "properties" nor "patternProperties".
     *
     * @param mixed        $data
     * @param CebeSchema[] $properties
     * @param mixed        $additionalProperties
     *
     * @throws SchemaMismatch
     */
    public function validate($data, array $properties): void
    {
        try {
            Validator::arrayType()->assert($data);
            Validator::arrayVal()->assert($properties);
            Validator::each(Validator::instance(CebeSchema::class))->assert($properties);
        } catch (Exception | ExceptionInterface $exception) {
            throw InvalidSchema::becauseDefensiveSchemaValidationFailed($exception);
        }

        // Validate existence of undefined field against schema
        $schema_fields = array_keys($properties);
        foreach ($data as $key => $v) {
            if (!in_array($key, $schema_fields)) {
                throw KeywordMismatch::fromKeyword(
                    'strict',
                    $data,
                    sprintf('field %s is not allowed', $key)
                )->withBreadCrumb($this->dataBreadCrumb->addCrumb($key));
            }
        }
    }
}
