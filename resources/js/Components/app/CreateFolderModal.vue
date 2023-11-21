<template>
    <modal :show="modelValue" @show="onShow" max-width="sm">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">Create New Folder</h2>
            <div class="mt-6">
                <InputLabel
                    for="folderName"
                    value="Folder Name"
                    class="sr-only"
                />
                <TextInput
                    type="text"
                    ref="folderNameInput"
                    id="folderName"
                    v-model="form.name"
                    class="mt-1 block w-full"
                    :class="
                        form.errors.name
                            ? 'border-red-500 focus:border-red-500 focus: ring-red-500'
                            : ''
                    "
                    placeholder="Folder Name"
                    @keyup.enter="createFolder"
                />
                <InputError :message="form.errors.name" />
            </div>
            <div class="mt-6 flex justify-center">
                <SecondaryButton
                    @click="closeModal"
                    :disabled="form.processing"
                >
                    Cancel
                </SecondaryButton>
                <PrimaryButton
                    class="ml-3"
                    :class="{ 'opacity-25': form.processing }"
                    @click="createFolder"
                >
                    Submit
                </PrimaryButton>
            </div>
        </div>
    </modal>
</template>
<script setup>
import Modal from "@/Components/Modal.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { nextTick, ref } from "vue";
import InputError from "@/Components/InputError.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const form = useForm({
    name: "",
    parent_id: null,
});

const page = usePage();

const folderNameInput = ref(null);
const emit = defineEmits(["update:modelValue"]);

const { modelValue } = defineProps({
    modelValue: Boolean,
});

function createFolder() {
    form.parent_id = page.props.folder.id;
    form.post(route("folder.create"), {
        preserveScroll: true,
        onSuccess: () => {
            console.log("is successed the call");
            closeModal();
        },
        onError: () => {
            folderNameInput.value.focus();
        },
    });
}

function onShow() {
    nextTick(() => folderNameInput.value.focus());
}

function closeModal() {
    emit("update:modelValue");
    form.clearErrors();
    form.reset();
}
</script>
