<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\UserFileCategory;
use App\Models\UserUploadedFile;
use App\Support\CollegeFlash;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class FileUploadsPage extends Component
{
    use WithFileUploads;
    use WithPagination;

    // View state
    public string $activeTab = 'files'; // 'files' or 'categories'

    // Filters & Sorting for Files
    public string $search = '';
    public string $categoryFilter = '';
    public string $typeFilter = '';
    public string $sortBy = 'created_at';
    public string $sortOrder = 'desc';
    public int $perPage = 10;

    // Pagination names (to avoid clashes)
    protected $queryString = [
        'search' => ['except' => ''],
        'categoryFilter' => ['except' => ''],
        'typeFilter' => ['except' => ''],
        'sortBy' => ['except' => 'created_at'],
        'sortOrder' => ['except' => 'desc'],
    ];

    // File Upload Modal State
    public bool $showUploadModal = false;
    public string $fileTitle = '';
    public string $fileCategory = '';
    public string $fileDescription = '';
    public $fileUpload; // TemporaryUploadedFile

    // Category Modal State
    public bool $showCategoryModal = false;
    public bool $isEditingCategory = false;
    public ?int $editingCategoryId = null;
    public string $categoryName = '';
    public string $categoryDescription = '';

    // Delete confirmation modals
    public bool $showDeleteFileModal = false;
    public ?int $deleteFileId = null;
    public bool $showDeleteCategoryModal = false;
    public ?int $deleteCategoryId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->canAdmin('admin.manage_file_uploads'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // --- File Upload Handlers ---

    public function openUploadModal(): void
    {
        $this->resetFileUploadForm();
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->resetFileUploadForm();
    }

    public function saveFile(): void
    {
        $userId = auth()->id();
        abort_unless($userId !== null, 403);

        $rules = [
            'fileTitle' => ['required', 'string', 'max:255'],
            'fileCategory' => ['nullable', 'exists:user_file_categories,id,user_id,'.$userId],
            'fileDescription' => ['nullable', 'string', 'max:1000'],
            'fileUpload' => ['required', 'file', 'max:20480'], // max 20MB
        ];

        $this->validate($rules);

        $disk = 'college_uploads';
        $originalFilename = $this->fileUpload->getClientOriginalName();
        $mimeType = $this->fileUpload->getMimeType() ?: 'application/octet-stream';
        $fileSize = $this->fileUpload->getSize();

        // Store file securely inside user-specific subdirectory
        $path = $this->fileUpload->store('user-files/'.$userId, $disk);

        UserUploadedFile::query()->create([
            'user_id' => $userId,
            'category_id' => $this->fileCategory !== '' ? (int) $this->fileCategory : null,
            'title' => $this->fileTitle,
            'original_filename' => $originalFilename,
            'file_path' => $path,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'description' => $this->fileDescription !== '' ? $this->fileDescription : null,
        ]);

        $this->closeUploadModal();
        CollegeFlash::forNextRequestToo('status', __('File uploaded successfully.'));
        $this->redirect(route('admin.file-uploads'), navigate: true);
    }

    private function resetFileUploadForm(): void
    {
        $this->resetValidation();
        $this->fileTitle = '';
        $this->fileCategory = '';
        $this->fileDescription = '';
        $this->fileUpload = null;
    }

    // --- Category CRUD Handlers ---

    public function openCreateCategory(): void
    {
        $this->resetCategoryForm();
        $this->isEditingCategory = false;
        $this->showCategoryModal = true;
    }

    public function openEditCategory(int $id): void
    {
        $userId = auth()->id();
        $category = UserFileCategory::query()
            ->where('user_id', $userId)
            ->findOrFail($id);

        $this->resetCategoryForm();
        $this->isEditingCategory = true;
        $this->editingCategoryId = $category->id;
        $this->categoryName = $category->name;
        $this->categoryDescription = $category->description ?? '';
        $this->showCategoryModal = true;
    }

    public function closeCategoryModal(): void
    {
        $this->showCategoryModal = false;
        $this->resetCategoryForm();
    }

    public function saveCategory(): void
    {
        $userId = auth()->id();
        abort_unless($userId !== null, 403);

        $this->validate([
            'categoryName' => ['required', 'string', 'max:255'],
            'categoryDescription' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->isEditingCategory && $this->editingCategoryId !== null) {
            $category = UserFileCategory::query()
                ->where('user_id', $userId)
                ->findOrFail($this->editingCategoryId);

            $category->update([
                'name' => $this->categoryName,
                'description' => $this->categoryDescription !== '' ? $this->categoryDescription : null,
            ]);

            CollegeFlash::forNextRequestToo('status', __('Category updated successfully.'));
        } else {
            UserFileCategory::query()->create([
                'user_id' => $userId,
                'name' => $this->categoryName,
                'description' => $this->categoryDescription !== '' ? $this->categoryDescription : null,
            ]);

            CollegeFlash::forNextRequestToo('status', __('Category created successfully.'));
        }

        $this->closeCategoryModal();
        $this->redirect(route('admin.file-uploads'), navigate: true);
    }

    private function resetCategoryForm(): void
    {
        $this->resetValidation();
        $this->categoryName = '';
        $this->categoryDescription = '';
        $this->editingCategoryId = null;
    }

    // --- Delete File Handlers ---

    public function confirmDeleteFile(int $id): void
    {
        $userId = auth()->id();
        $file = UserUploadedFile::query()
            ->where('user_id', $userId)
            ->findOrFail($id);

        $this->deleteFileId = $file->id;
        $this->showDeleteFileModal = true;
    }

    public function deleteFile(): void
    {
        if ($this->deleteFileId === null) {
            return;
        }

        $userId = auth()->id();
        $file = UserUploadedFile::query()
            ->where('user_id', $userId)
            ->findOrFail($this->deleteFileId);

        // Delete from storage
        Storage::disk('college_uploads')->delete($file->file_path);

        $file->delete();

        $this->showDeleteFileModal = false;
        $this->deleteFileId = null;

        CollegeFlash::forNextRequestToo('status', __('File deleted successfully.'));
        $this->redirect(route('admin.file-uploads'), navigate: true);
    }

    // --- Delete Category Handlers ---

    public function confirmDeleteCategory(int $id): void
    {
        $userId = auth()->id();
        $category = UserFileCategory::query()
            ->where('user_id', $userId)
            ->findOrFail($id);

        $this->deleteCategoryId = $category->id;
        $this->showDeleteCategoryModal = true;
    }

    public function deleteCategory(): void
    {
        if ($this->deleteCategoryId === null) {
            return;
        }

        $userId = auth()->id();
        $category = UserFileCategory::query()
            ->where('user_id', $userId)
            ->findOrFail($this->deleteCategoryId);

        // Deleting category will set category_id to NULL on user_uploaded_files due to migrations
        $category->delete();

        $this->showDeleteCategoryModal = false;
        $this->deleteCategoryId = null;

        CollegeFlash::forNextRequestToo('status', __('Category deleted successfully.'));
        $this->redirect(route('admin.file-uploads'), navigate: true);
    }

    // --- Render Page ---

    public function render(): View
    {
        $userId = auth()->id();

        // 1. Fetch categories
        $categories = UserFileCategory::query()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get();

        // 2. Query files with filtering and sorting
        $query = UserUploadedFile::query()
            ->where('user_id', $userId)
            ->with('category');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', '%'.$this->search.'%')
                  ->orWhere('original_filename', 'like', '%'.$this->search.'%')
                  ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->categoryFilter !== '') {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->typeFilter !== '') {
            if ($this->typeFilter === 'image') {
                $query->where('mime_type', 'like', 'image/%');
            } elseif ($this->typeFilter === 'pdf') {
                $query->where('mime_type', 'application/pdf');
            } elseif ($this->typeFilter === 'document') {
                $query->where(function ($q) {
                    $q->whereIn('mime_type', [
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/plain',
                        'application/rtf',
                        'text/csv',
                    ]);
                });
            } elseif ($this->typeFilter === 'audio_video') {
                $query->where(function ($q) {
                    $q->where('mime_type', 'like', 'audio/%')
                      ->orWhere('mime_type', 'like', 'video/%');
                });
            } else {
                // other
                $query->where('mime_type', 'not like', 'image/%')
                      ->where('mime_type', 'not like', 'audio/%')
                      ->where('mime_type', 'not like', 'video/%')
                      ->whereNotIn('mime_type', [
                          'application/pdf',
                          'application/msword',
                          'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                          'application/vnd.ms-excel',
                          'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                          'text/plain',
                          'application/rtf',
                          'text/csv',
                      ]);
            }
        }

        // Apply sort
        $allowedSorts = ['created_at', 'title', 'file_size'];
        $actualSortBy = in_array($this->sortBy, $allowedSorts, true) ? $this->sortBy : 'created_at';
        $actualSortOrder = $this->sortOrder === 'asc' ? 'asc' : 'desc';

        $query->orderBy($actualSortBy, $actualSortOrder);

        $files = $query->paginate($this->perPage, ['*'], 'filesPage');

        // 3. Query categories for management tab
        $categoriesWithCounts = UserFileCategory::query()
            ->where('user_id', $userId)
            ->withCount('files')
            ->orderBy('name')
            ->paginate($this->perPage, ['*'], 'categoriesPage');

        return view('livewire.admin.file-uploads-page', [
            'categories' => $categories,
            'files' => $files,
            'categoriesWithCounts' => $categoriesWithCounts,
        ])->layout('components.layouts.admin', [
            'title' => __('File Manager'),
            'headerTitle' => __('File Manager'),
            'headerDescription' => __('Define your own file categories and securely upload files under those categories.'),
        ]);
    }
}
