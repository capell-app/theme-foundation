@props([
    'page',
    'site',
])

<style>
    .theme-demo-contact-page {
        background:
            linear-gradient(
                135deg,
                rgba(15, 118, 110, 0.08),
                transparent 34rem
            ),
            #faf8ff;
        color: #131b2e;
        min-height: 100vh;
        padding: clamp(48px, 8vw, 96px) clamp(20px, 6vw, 72px);
    }

    .theme-demo-contact-shell {
        display: grid;
        gap: clamp(32px, 5vw, 72px);
        margin: 0 auto;
        max-width: 1240px;
    }

    .theme-demo-contact-home {
        color: #131b2e;
        font-size: 1rem;
        font-weight: 800;
        text-decoration: none;
    }

    .theme-demo-contact-gateway {
        display: grid;
        gap: clamp(32px, 5vw, 64px);
    }

    .theme-demo-contact-intro {
        display: grid;
        gap: 28px;
    }

    .theme-demo-contact-eyebrow,
    .theme-demo-contact-routing article > p:first-child,
    .theme-demo-contact-form label {
        color: #0f766e;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0;
        margin: 0;
        text-transform: uppercase;
    }

    .theme-demo-contact-page h2,
    .theme-demo-contact-page h3 {
        letter-spacing: 0;
    }

    .theme-demo-contact-page h2 {
        color: #131b2e;
        font-size: clamp(3rem, 7vw, 5.25rem);
        font-weight: 850;
        line-height: 0.98;
        margin: 0;
        max-width: 10ch;
    }

    .theme-demo-contact-lede {
        color: #475569;
        font-size: 1.08rem;
        line-height: 1.7;
        margin: 0;
        max-width: 44rem;
    }

    .theme-demo-contact-routing {
        border-top: 1px solid rgba(148, 163, 184, 0.42);
        display: grid;
        gap: 0;
    }

    .theme-demo-contact-routing article {
        border-bottom: 1px solid rgba(148, 163, 184, 0.42);
        display: grid;
        gap: 12px;
        padding: 22px 0;
    }

    .theme-demo-contact-routing h3 {
        color: #131b2e;
        font-size: 1.15rem;
        line-height: 1.2;
        margin: 0;
    }

    .theme-demo-contact-routing article p:last-child,
    .theme-demo-contact-details p,
    .theme-demo-contact-expectations p {
        color: #475569;
        line-height: 1.65;
        margin: 0;
    }

    .theme-demo-contact-details {
        border-bottom: 1px solid rgba(148, 163, 184, 0.42);
        border-top: 1px solid rgba(148, 163, 184, 0.42);
        display: grid;
        gap: 18px;
        padding: 24px 0;
    }

    .theme-demo-contact-details h3 {
        color: #131b2e;
        font-size: clamp(1.45rem, 3vw, 2rem);
        font-weight: 850;
        line-height: 1.1;
        margin: 0;
    }

    .theme-demo-contact-form {
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid rgba(226, 232, 240, 0.95);
        border-radius: 8px;
        box-shadow: 0 28px 90px rgba(15, 23, 42, 0.12);
        display: grid;
        gap: 16px;
        padding: clamp(22px, 4vw, 34px);
    }

    .theme-demo-contact-form-header {
        display: grid;
        gap: 8px;
        margin-bottom: 8px;
    }

    .theme-demo-contact-form-header h3 {
        color: #131b2e;
        font-size: 1.65rem;
        font-weight: 850;
        line-height: 1.12;
        margin: 0;
    }

    .theme-demo-contact-form-header p {
        color: #64748b;
        line-height: 1.6;
        margin: 0;
    }

    .theme-demo-contact-field {
        display: grid;
        gap: 8px;
    }

    .theme-demo-contact-form input,
    .theme-demo-contact-form select,
    .theme-demo-contact-form textarea {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        color: #0f172a;
        font: inherit;
        min-height: 46px;
        padding: 10px 12px;
        width: 100%;
    }

    .theme-demo-contact-form textarea {
        min-height: 120px;
        resize: vertical;
    }

    .theme-demo-contact-form button {
        align-items: center;
        background: #0f766e;
        border: 0;
        border-radius: 8px;
        color: #ffffff;
        display: inline-flex;
        font: inherit;
        font-weight: 800;
        justify-content: center;
        min-height: 48px;
        padding: 12px 18px;
    }

    .theme-demo-contact-expectations {
        border-top: 1px solid rgba(148, 163, 184, 0.42);
        display: grid;
        gap: 0;
        margin-top: 8px;
    }

    .theme-demo-contact-expectations p {
        border-bottom: 1px solid rgba(148, 163, 184, 0.42);
        padding: 16px 0;
    }

    .theme-demo-contact-expectations strong {
        color: #131b2e;
    }

    @media (min-width: 760px) {
        .theme-demo-contact-routing article,
        .theme-demo-contact-details {
            grid-template-columns: minmax(10rem, 0.48fr) minmax(0, 1fr);
        }

        .theme-demo-contact-expectations {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .theme-demo-contact-expectations p {
            border-bottom: 0;
            border-left: 1px solid rgba(148, 163, 184, 0.42);
            padding: 0 20px;
        }

        .theme-demo-contact-expectations p:first-child {
            border-left: 0;
            padding-left: 0;
        }
    }

    @media (min-width: 1024px) {
        .theme-demo-contact-gateway {
            align-items: start;
            grid-template-columns: minmax(0, 1fr) minmax(22rem, 0.72fr);
        }

        .theme-demo-contact-form {
            position: sticky;
            top: 32px;
        }
    }
</style>

<section
    id="contact"
    class="theme-demo-contact-page"
>
    <div class="theme-demo-contact-shell">
        <a
            class="theme-demo-contact-home"
            href="{{ $site->defaultDomain?->url ?? $site->siteDomain?->url ?? '/' }}"
        >
            {{ $site->translation->title ?? $site->name }}
        </a>

        <h1 class="sr-only">{{ $page->translation->title ?? $page->name }}</h1>

        <div class="theme-demo-contact-gateway">
            <div class="theme-demo-contact-intro">
                <p class="theme-demo-contact-eyebrow">Contact</p>
                <h2>Start the right conversation</h2>
                <p class="theme-demo-contact-lede">
                    Tell us what you are planning, fixing, moving, or partnering
                    on. One contact page routes project scoping, technical
                    support, migrations, and partnerships to the right Capell
                    team.
                </p>

                <div class="theme-demo-contact-details">
                    <h3>Capell Studio, London</h3>
                    <p>
                        Remote-first delivery with UK timezone handover. Send an
                        enquiry and the contact form routes it into the right
                        follow-up path.
                    </p>
                </div>

                <div class="theme-demo-contact-routing">
                    @foreach ([['Project scoping', 'New implementations', 'Plan content models, package boundaries, layouts, and launch checks before the build starts.'], ['Support', 'Existing site help', 'Route production issues, editor workflow questions, and package troubleshooting to the right owner.'], ['Migration planning', 'Move from legacy CMSs', 'Map pages, redirects, media, structured fields, and verification work into a clear migration path.'], ['Partnerships', 'Agency and technology work', 'Discuss delivery partnerships, packaged integrations, and repeatable theme or content operations.']] as [$label, $title, $copy])
                        <article>
                            <p>{{ $label }}</p>
                            <div>
                                <h3>{{ $title }}</h3>
                                <p>{{ $copy }}</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            <form
                id="contact-form"
                class="theme-demo-contact-form theme-demo-contact-form-panel"
                method="post"
                action="#"
            >
                <div class="theme-demo-contact-form-header">
                    <p class="theme-demo-contact-eyebrow">Contact form</p>
                    <h3>Send an enquiry</h3>
                    <p>
                        Share the context once. We will route it to the right
                        delivery, support, migration, or partnership lead.
                    </p>
                </div>

                @foreach ([['theme-demo-contact-name', 'name', 'text', 'Name', 'name'], ['theme-demo-contact-email', 'email', 'email', 'Work email', 'email'], ['theme-demo-contact-company', 'company', 'text', 'Company', 'organization']] as [$id, $name, $type, $label, $autocomplete])
                    <div class="theme-demo-contact-field">
                        <label for="{{ $id }}">{{ $label }}</label>
                        <input
                            id="{{ $id }}"
                            name="{{ $name }}"
                            type="{{ $type }}"
                            autocomplete="{{ $autocomplete }}"
                        />
                    </div>
                @endforeach

                <div class="theme-demo-contact-field">
                    <label for="theme-demo-contact-topic">Topic</label>
                    <select
                        id="theme-demo-contact-topic"
                        name="topic"
                    >
                        <option>Project scoping</option>
                        <option>Support</option>
                        <option>Migration planning</option>
                        <option>Partnerships</option>
                    </select>
                </div>

                <div class="theme-demo-contact-field">
                    <label for="theme-demo-contact-message">Message</label>
                    <textarea
                        id="theme-demo-contact-message"
                        name="message"
                        rows="5"
                    ></textarea>
                </div>

                <button type="button">Send enquiry</button>
            </form>
        </div>

        <div
            class="theme-demo-contact-expectations"
            aria-label="Contact expectations"
        >
            <p>
                <strong>Response:</strong>
                Within 4 business hours
            </p>
            <p>
                <strong>Location:</strong>
                London, UK and remote-first
            </p>
            <p>
                <strong>Handover:</strong>
                Directly routed to the right team
            </p>
        </div>
    </div>
</section>
