<section class="card p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Company Logos</h2>
        <button type="button" class="btn btn-outline btn-sm" @click="logos.push({ path: '' })">Add Logo</button>
    </div>
    <template x-for="(item, index) in logos" :key="`logo-${index}`">
        <div class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 p-4 md:grid-cols-[1fr_auto] dark:border-gray-700">
            <div>
                <label class="label">Logo Path</label>
                <input class="input" :name="`logos[${index}][path]`" x-model="item.path" placeholder="/images/brand/brand-01.svg">
            </div>
            <div class="flex items-end pb-2">
                <button type="button" class="btn btn-outline btn-xs" @click="logos.splice(index, 1)">Remove</button>
            </div>
        </div>
    </template>
</section>
