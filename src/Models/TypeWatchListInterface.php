<?php

declare(strict_types=1);

namespace Seatplus\Eveapi\Models;

/**
 * Marks a model as filterable by a type/group/category watchlist, so consumers
 * can branch on `$model instanceof TypeWatchListInterface`.
 *
 * The filter contract itself is {@see FiltersByTypeWatchList}, whose
 * `abstract protected` declarations implementors must satisfy. It cannot live on
 * this interface: interface methods must be public, and public `#[Scope]` methods
 * are exactly the ones Larastan will not resolve on the query builder, which
 * breaks static analysis in every consuming package. Declaring them here again
 * would reintroduce that bug.
 */
interface TypeWatchListInterface {}
