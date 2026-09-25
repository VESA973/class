import { useEffect } from 'react';
import { EditorContent, useEditor, useEditorState, type Editor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    Heading2,
    Heading3,
    Italic,
    Link2,
    Link2Off,
    List,
    ListOrdered,
    Minus,
    Pilcrow,
    Quote,
    Redo2,
    Undo2,
} from 'lucide-react';
import { cn } from 'cn';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type Props = {
    /** Selecteur de la zone de texte du formulaire Blade qui recoit le HTML (envoye au serveur, qui le nettoie). */
    target: string;
    variables: Record<string, string>;
};

/** Editeur de texte riche des pages legales (Tiptap). Le formulaire reste un formulaire classique. */
export default function LegalEditor({ target, variables }: Props) {
    const textarea = document.querySelector<HTMLTextAreaElement>(target);

    const editor = useEditor({
        extensions: [StarterKit.configure({ heading: { levels: [2, 3, 4] }, link: { openOnClick: false, autolink: true } })],
        content: textarea?.value ?? '',
        immediatelyRender: true,
        editorProps: {
            attributes: {
                class: 'legal-editor-content min-h-[420px] rounded-b-xl border border-t-0 border-border bg-background px-5 py-4 text-[15px] leading-relaxed outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40',
                'aria-label': 'Contenu de la page',
            },
        },
        onUpdate: ({ editor: current }) => {
            if (textarea) textarea.value = current.getHTML();
        },
    });

    // La zone de texte classique reste la source envoyee au serveur ; on la masque une fois l'editeur pret.
    useEffect(() => {
        if (textarea) textarea.hidden = true;

        return () => {
            if (textarea) textarea.hidden = false;
        };
    }, [textarea]);

    if (!editor) return null;

    return (
        <div className="grid">
            <Toolbar editor={editor} variables={variables} />
            <EditorContent editor={editor} />
        </div>
    );
}

function Toolbar({ editor, variables }: { editor: Editor; variables: Record<string, string> }) {
    const state = useEditorState({
        editor,
        selector: ({ editor: e }) => ({
            h2: e.isActive('heading', { level: 2 }),
            h3: e.isActive('heading', { level: 3 }),
            paragraph: e.isActive('paragraph'),
            bold: e.isActive('bold'),
            italic: e.isActive('italic'),
            bullet: e.isActive('bulletList'),
            ordered: e.isActive('orderedList'),
            quote: e.isActive('blockquote'),
            link: e.isActive('link'),
            canUndo: e.can().undo(),
            canRedo: e.can().redo(),
        }),
    });

    function setLink() {
        const previous = editor.getAttributes('link').href as string | undefined;
        const url = window.prompt('Adresse du lien (https://…, mailto:… ou /page)', previous ?? 'https://');

        if (url === null) return;
        if (url === '') {
            editor.chain().focus().unsetLink().run();
            return;
        }
        editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
    }

    const chain = () => editor.chain().focus();

    return (
        <div className="sticky top-0 z-10 flex flex-wrap items-center gap-1 rounded-t-xl border border-border bg-card p-1.5" role="toolbar" aria-label="Mise en forme">
            <ToolButton label="Titre de section" active={state.h2} onClick={() => chain().toggleHeading({ level: 2 }).run()}><Heading2 /></ToolButton>
            <ToolButton label="Sous-titre" active={state.h3} onClick={() => chain().toggleHeading({ level: 3 }).run()}><Heading3 /></ToolButton>
            <ToolButton label="Paragraphe" active={state.paragraph} onClick={() => chain().setParagraph().run()}><Pilcrow /></ToolButton>
            <Separator />
            <ToolButton label="Gras" active={state.bold} onClick={() => chain().toggleBold().run()}><Bold /></ToolButton>
            <ToolButton label="Italique" active={state.italic} onClick={() => chain().toggleItalic().run()}><Italic /></ToolButton>
            <Separator />
            <ToolButton label="Liste à puces" active={state.bullet} onClick={() => chain().toggleBulletList().run()}><List /></ToolButton>
            <ToolButton label="Liste numérotée" active={state.ordered} onClick={() => chain().toggleOrderedList().run()}><ListOrdered /></ToolButton>
            <ToolButton label="Citation" active={state.quote} onClick={() => chain().toggleBlockquote().run()}><Quote /></ToolButton>
            <ToolButton label="Ligne de séparation" onClick={() => chain().setHorizontalRule().run()}><Minus /></ToolButton>
            <Separator />
            <ToolButton label="Ajouter un lien" active={state.link} onClick={setLink}><Link2 /></ToolButton>
            <ToolButton label="Retirer le lien" disabled={!state.link} onClick={() => chain().unsetLink().run()}><Link2Off /></ToolButton>
            <Separator />
            <ToolButton label="Annuler" disabled={!state.canUndo} onClick={() => chain().undo().run()}><Undo2 /></ToolButton>
            <ToolButton label="Rétablir" disabled={!state.canRedo} onClick={() => chain().redo().run()}><Redo2 /></ToolButton>
            <div className="ml-auto">
                <Select value="" onValueChange={(variable) => chain().insertContent(`{${variable}}`).run()}>
                    <SelectTrigger size="sm" className="w-56" aria-label="Insérer une information de l’entreprise">
                        <SelectValue placeholder="Insérer une information…" />
                    </SelectTrigger>
                    <SelectContent className="max-h-72">
                        {Object.entries(variables).map(([variable, label]) => (
                            <SelectItem key={variable} value={variable}>
                                {label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
        </div>
    );
}

function ToolButton({ label, active, disabled, onClick, children }: { label: string; active?: boolean; disabled?: boolean; onClick: () => void; children: React.ReactNode }) {
    return (
        <button
            type="button"
            title={label}
            aria-label={label}
            aria-pressed={active}
            disabled={disabled}
            onClick={onClick}
            className={cn(
                'grid size-8 place-items-center rounded-md text-muted-foreground transition hover:bg-accent hover:text-foreground disabled:opacity-40 [&_svg]:size-4',
                active && 'bg-accent text-foreground',
            )}
        >
            {children}
        </button>
    );
}

function Separator() {
    return <span aria-hidden className="mx-1 h-5 w-px bg-border" />;
}
