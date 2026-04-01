<section class="card p-6 space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Plans</h2>
        <button type="button" class="btn btn-outline btn-sm" @click="plans.push({ name: '', price: '', desc: '', cta: '', highlight: false })">Add Plan</button>
    </div>
    <template x-for="(item, index) in plans" :key="`plan-${index}`">
        <div class="space-y-3 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label class="label">Plan Name</label>
                    <input class="input" :name="`plans[${index}][name]`" x-model="item.name">
                </div>
                <div>
                    <label class="label">Price Label</label>
                    <input class="input" :name="`plans[${index}][price]`" x-model="item.price">
                </div>
            </div>
            <div>
                <label class="label">Description</label>
                <input class="input" :name="`plans[${index}][desc]`" x-model="item.desc">
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div>
                    <label class="label">CTA Text</label>
                    <input class="input" :name="`plans[${index}][cta]`" x-model="item.cta">
                </div>
                <div class="flex items-end pb-2">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <input type="hidden" :name="`plans[${index}][highlight]`" value="0">
                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300" :name="`plans[${index}][highlight]`" value="1" x-model="item.highlight">
                        <span>Highlight plan</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="button" class="btn btn-outline btn-xs" @click="plans.splice(index, 1)">Remove</button>
            </div>
        </div>
    </template>
</section>
