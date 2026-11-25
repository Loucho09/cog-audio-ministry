<x-public-layout
    title="Thank You"
    description="Your application to join the Audio Ministry was received. The branch will get back to you soon."
    :transparent-nav="false"
>
    <section class="mx-auto w-full max-w-2xl px-4 pb-20 pt-32 text-center sm:px-6">
        <h1 class="text-3xl font-extrabold tracking-tight text-text sm:text-4xl">Thank you for applying</h1>
        <p class="mt-4 text-base text-text/80">
            We received your application. The branch you chose will look at it and get back to you. God bless you for your heart to serve.
        </p>
        <div class="mt-8">
            <x-site.button :href="route('home')" size="lg">Back to home</x-site.button>
        </div>
    </section>
</x-public-layout>
