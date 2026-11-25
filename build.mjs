import { mkdir, readFile, readdir, unlink, writeFile } from 'node:fs/promises'
import { fileURLToPath } from 'node:url'
import { build } from 'esbuild'

// Builds the Foundation frontend runtime into publishes/build. The PHP side
// reads manifest.json keyed by the entry source path and publishes the hashed
// files; each optional widget module stays a separate chunk that the runtime
// imports only when its markup is on the page.
const entry = 'resources/js/capell-frontend.js'
const packageDirectory = fileURLToPath(new URL('.', import.meta.url))
const outputDirectory = new URL('publishes/build/', import.meta.url)
const assetDirectory = new URL('assets/', outputDirectory)
const manifestPath = new URL('manifest.json', outputDirectory)
const checkOnly = process.argv.includes('--check')

const sharedOptions = {
    absWorkingDir: packageDirectory,
    bundle: true,
    format: 'esm',
    legalComments: 'none',
    metafile: true,
    platform: 'browser',
    target: ['es2022'],
    write: false,
}

// First pass: discover the modules the runtime loads with import(), so each
// becomes a named entry chunk rather than an anonymous shared chunk.
const discovery = await build({
    ...sharedOptions,
    entryPoints: [entry],
    outdir: 'publishes/build/assets',
    splitting: true,
})
const dynamicEntries = discovery.metafile.inputs[entry].imports
    .filter((imported) => imported.kind === 'dynamic-import')
    .map((imported) => imported.path)

const result = await build({
    ...sharedOptions,
    chunkNames: 'chunk-[hash]',
    entryNames: '[name]-[hash]',
    entryPoints: [entry, ...dynamicEntries],
    minify: true,
    outdir: 'publishes/build/assets',
    splitting: true,
})

const assetPrefix = 'publishes/build/'
const outputs = Object.entries(result.metafile.outputs)
const fileFor = (outputPath) => outputPath.slice(assetPrefix.length)
const keyFor = (outputPath) => {
    const output = result.metafile.outputs[outputPath]

    return output.entryPoint ?? `_${outputPath.split('/').pop()}`
}
const manifestEntries = outputs.map(([outputPath, output]) => {
    const file = fileFor(outputPath)
    const staticImports = output.imports
        .filter((imported) => imported.kind === 'import-statement')
        .map((imported) => keyFor(imported.path))
    const dynamicImports = output.imports
        .filter((imported) => imported.kind === 'dynamic-import')
        .map((imported) => keyFor(imported.path))
    const record = {
        file,
        name: file
            .split('/')
            .pop()
            .replace(/-[A-Z0-9]+\.js$/u, ''),
    }

    if (output.entryPoint !== undefined) {
        record.src = output.entryPoint

        if (output.entryPoint === entry) {
            record.isEntry = true
        } else {
            record.isDynamicEntry = true
        }
    }

    if (staticImports.length > 0) {
        record.imports = staticImports
    }

    if (dynamicImports.length > 0) {
        record.dynamicImports = dynamicImports
    }

    return [keyFor(outputPath), record]
})
manifestEntries.sort(([left], [right]) => left.localeCompare(right))

const manifest = `${JSON.stringify(Object.fromEntries(manifestEntries), null, 4)}\n`
const expectedFiles = new Map(
    result.outputFiles.map((outputFile) => [
        outputFile.path.split('/').pop(),
        outputFile.text,
    ]),
)

if (checkOnly) {
    const committedFiles = (await readdir(assetDirectory)).filter((file) =>
        file.endsWith('.js'),
    )
    const committedManifest = await readFile(manifestPath, 'utf8').catch(
        () => '',
    )
    const stale =
        committedManifest !== manifest ||
        committedFiles.length !== expectedFiles.size ||
        (
            await Promise.all(
                committedFiles.map(async (file) =>
                    expectedFiles.has(file)
                        ? (await readFile(
                              new URL(file, assetDirectory),
                              'utf8',
                          )) === expectedFiles.get(file)
                        : false,
                ),
            )
        ).includes(false)

    if (stale) {
        throw new Error(
            'Foundation frontend assets are stale. Run npm run build and commit publishes/build.',
        )
    }
} else {
    await mkdir(assetDirectory, { recursive: true })

    for (const existingFile of await readdir(assetDirectory)) {
        if (existingFile.endsWith('.js') && !expectedFiles.has(existingFile)) {
            await unlink(new URL(existingFile, assetDirectory))
        }
    }

    await Promise.all([
        ...[...expectedFiles].map(([file, text]) =>
            writeFile(new URL(file, assetDirectory), text),
        ),
        writeFile(manifestPath, manifest),
    ])
}
