<?php

namespace Dashworthy\Visualizations\Enums;

enum FilterOperator: string
{
    case STRING_STARTS_WITH = 'startsWith';
    case STRING_CONTAINS = 'contains';
    case STRING_DOES_NOT_CONTAIN = 'doesNotContain';
    case STRING_ENDS_WITH = 'endsWith';
    case EQUALS = 'equals';
    case NOT_EQUALS = 'doesNotEquals';
    case IN = 'in';
    case NOT_IN = 'notIn';
    case LESS_THAN = 'lt';
    case LESS_THAN_OR_EQUAL_TO = 'lte';
    case GREATER_THAN = 'gt';
    case GREATER_THAN_OR_EQUAL_TO = 'gte';

    /**
     * Whether a request value for this operator runs through the configured normalizers. A text search term is
     * matched as typed, so searching for "on" or "null" doesn't become a search for true or for anything.
     */
    public function normalizesValue(): bool
    {
        return match ($this) {
            self::STRING_STARTS_WITH,
            self::STRING_CONTAINS,
            self::STRING_DOES_NOT_CONTAIN,
            self::STRING_ENDS_WITH => false,
            default => true,
        };
    }
}
