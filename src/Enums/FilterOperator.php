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
}
