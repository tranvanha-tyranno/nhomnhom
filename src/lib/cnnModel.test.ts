import { describe, expect, it } from "vitest";

import { createCnnModel } from "./cnnModel";

describe("createCnnModel", () => {
  it("creates a compiled CNN for image classification", () => {
    const model = createCnnModel({ classCount: 4 });

    expect(model.inputs[0]?.shape).toEqual([null, 64, 64, 3]);
    expect(model.outputs[0]?.shape).toEqual([null, 4]);
    expect(model.layers.map((layer) => layer.getClassName())).toEqual([
      "Conv2D",
      "MaxPooling2D",
      "Conv2D",
      "MaxPooling2D",
      "Flatten",
      "Dense",
      "Dense"
    ]);
    expect(model.optimizer).toBeDefined();
  });

  it("supports a custom image shape", () => {
    const model = createCnnModel({
      classCount: 2,
      inputShape: [32, 48, 1]
    });

    expect(model.inputs[0]?.shape).toEqual([null, 32, 48, 1]);
    expect(model.outputs[0]?.shape).toEqual([null, 2]);
  });

  it.each([0, 1, 1.5, Number.NaN])(
    "rejects invalid class count %s",
    (classCount) => {
      expect(() => createCnnModel({ classCount })).toThrow(
        "classCount must be an integer greater than 1."
      );
    }
  );
});
