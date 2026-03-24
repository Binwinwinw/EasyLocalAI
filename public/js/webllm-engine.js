/**
 * EasyLocalAI - Browser Mode Engine (WebLLM)
 * Permet de faire tourner l'IA directement dans le navigateur via WebGPU.
 */

import * as webllm from "https://esm.run/@mlc-ai/web-llm";

export class WebLLMEngine {
    constructor(modelId = "Llama-3.2-1B-Instruct-q4f16_1-MLC") {
        this.modelId = modelId;
        this.engine = null;
        this.onProgress = null;
    }

    async init(onProgress) {
        if (this.engine) return;
        this.onProgress = onProgress;
        
        this.engine = await webllm.CreateMLCEngine(this.modelId, {
            initProgressCallback: (report) => {
                if (this.onProgress) this.onProgress(report);
            }
        });
    }

    async generate(messages, onUpdate, onFinish) {
        if (!this.engine) throw new Error("Moteur non initialisé");

        const chunks = await this.engine.chat.completions.create({
            messages,
            stream: true,
        });

        let fullText = "";
        for await (const chunk of chunks) {
            const content = chunk.choices[0]?.delta?.content || "";
            fullText += content;
            if (onUpdate) onUpdate(content);
        }

        if (onFinish) onFinish(fullText);
    }

    async checkWebGPU() {
        if (!navigator.gpu) {
            return false;
        }
        try {
            const adapter = await navigator.gpu.requestAdapter();
            return !!adapter;
        } catch (e) {
            return false;
        }
    }
}
