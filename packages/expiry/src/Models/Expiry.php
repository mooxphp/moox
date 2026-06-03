<?php

namespace Moox\Expiry\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Moox\Core\Traits\SoftDelete\SingleSoftDeleteInModel;
use Moox\Press\Models\WpUser;
use Moox\Press\QueryBuilder\UserQueryBuilder;
use Override;

/**
 * @property int $id
 * @property string|null $title
 * @property string|null $slug
 * @property int|null $item_id
 * @property int|null $meta_id
 * @property string|null $link
 * @property string|null $expiry_job
 * @property string|null $category
 * @property string|null $status
 * @property Carbon|null $expired_at
 * @property Carbon|null $processing_deadline
 * @property string|null $cycle
 * @property Carbon|null $notified_at
 * @property int|null $notified_to
 * @property Carbon|null $escalated_at
 * @property int|null $escalated_to
 * @property int|null $handled_by
 * @property Carbon|null $done_at
 * @property-read WpUser|null $notifyUser
 * @property-read WpUser|null $escalateUser
 */
class Expiry extends Model
{
    use HasFactory;
    use SingleSoftDeleteInModel;

    protected $fillable = [
        'title',
        'slug',
        'item_id',
        'meta_id',
        'link',
        'expiry_job',
        'category',
        'status',
        'expired_at',
        'processing_deadline',
        'cycle',
        'notified_at',
        'notified_to',
        'escalated_at',
        'escalated_to',
        'handled_by',
        'done_at',
    ];

    protected $searchableFields = ['*'];

    protected $casts = [
        'expired_at' => 'datetime',
        'notified_at' => 'datetime',
        'escalated_at' => 'datetime',
        'done_at' => 'datetime',
    ];

    /**
     * Get the owning user model for notify.
     */
    public function notifyUser(): BelongsTo
    {
        return $this->belongsTo(config('expiry.user_model'), 'notified_to', 'ID');
    }

    /**
     * Get the owning user model for escalate.
     */
    public function escalateUser(): BelongsTo
    {
        return $this->belongsTo(config('expiry.user_model'), 'escalated_to', 'ID');
    }

    /**
     * Use the custom query builder for the model.
     */
    #[Override]
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return new UserQueryBuilder(
            $connection, $connection->getQueryGrammar(), $connection->getPostProcessor()
        );
    }

    public static function getExpiryJobOptions(): Collection
    {
        return static::select('expiry_job')
            ->distinct()
            ->whereNotNull('expiry_job')
            ->pluck('expiry_job', 'expiry_job');
    }

    public static function getExpiryCategoryOptions(): Collection
    {
        return static::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->pluck('category', 'category');
    }

    public static function getExpiryStatusOptions(): Collection
    {
        return static::select('status')
            ->distinct()
            ->whereNotNull('status')
            ->pluck('status', 'status');
    }

    public static function getUserOptions(): array
    {
        $userModel = config('expiry.user_model');

        if (! class_exists($userModel)) {
            throw new Exception(sprintf('User model class %s does not exist.', $userModel));
        }

        $notifiedToUserIds = Expiry::pluck('notified_to')->unique()->filter();

        if ($notifiedToUserIds->isEmpty()) {
            return [];
        }

        $users = $userModel::whereIn('ID', $notifiedToUserIds)
            ->get(['ID', 'display_name']);

        return $users
            ->pluck('display_name', 'ID')
            ->filter(fn ($displayName): bool => ! is_null($displayName))
            ->sortBy(fn ($displayName, $id) => strtolower((string) $displayName))
            ->toArray();
    }
}
