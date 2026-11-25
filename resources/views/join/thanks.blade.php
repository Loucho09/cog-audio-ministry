<x-public-layout
    title="Thank You"
    description="Your application to join the Audio Ministry was received. If you are approved, you will receive an email to set up your account."
    :transparent-nav="false"
>
    <section class="mx-auto w-full max-w-2xl px-4 pb-20 pt-32 text-center sm:px-6">
        <h1 class="text-3xl font-extrabold tracking-tight text-text sm:text-4xl">Thank you for applying</h1>
        <p class="mt-4 text-base text-text/80">
            We received your application. The leaders of the branch you chose will review it.
        </p>
        <p class="mt-3 text-base text-text/80">
            <strong class="font-semibold text-text">If you are approved, you will receive an email</strong>
            with a link to set your password and log in to your member account. Please check your inbox, and your spam folder too.
        </p>
        <p class="mt-3 text-base text-text/80">
            God bless you for your heart to serve.
        </p>
        <div class="mt-8">
            <x-site.button :href="route('home')" size="lg">Back to home</x-site.button>
        </div>
    </section>
</x-public-layout>
