namespace App\Services;

use App\Models\Category;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryService
{
    public function getCategories(array $filters = []): LengthAwarePaginator
    {
        $query = Category::query();
        
        if (isset($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }
        
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function getCategoryById(int $id): Category
    {
        return Category::findOrFail($id);
    }

    public function createCategory(array $data): Category
    {
        return Category::create($data);
    }

    public function updateCategory(int $id, array $data): Category
    {
        $category = $this->getCategoryById($id);
        $category->update($data);
        return $category;
    }

    public function deleteCategory(int $id): bool
    {
        $category = $this->getCategoryById($id);
        return $category->delete();
    }

    public function getCategoryTree(): array
    {
        return Category::whereNull('parent_id')
            ->with('children')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    public function getFeaturedCategories(): array
    {
        return Category::where('is_featured', true)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->toArray();
    }

    public function updateCategoryStatus(int $id, bool $isActive): Category
    {
        $category = $this->getCategoryById($id);
        $category->update(['is_active' => $isActive]);
        return $category;
    }

    public function reorderCategories(array $categories): void
    {
        foreach ($categories as $categoryData) {
            Category::where('id', $categoryData['id'])
                ->update(['sort_order' => $categoryData['sort_order']]);
        }
    }
}