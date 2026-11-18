{{-- Livewire pagination (ITK Pagination). The file name is kept so ->links('vendor.livewire.daisy-pagination') still works. --}}
@if ($paginator->hasPages())
    <x-itk.pagination :paginator="$paginator" :elements="$elements" livewire />
@endif
